<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Autowired;

use Nette;
use Nette\Reflection\Method;
use Nette\Reflection\Property;
use Nette\Reflection\ClassType;
use Nette\Utils\Callback;
use Nette\Utils\Strings;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait AutowireProperties
{

	/**
	 * @var array
	 */
	private $autowireProperties = [];

	/**
	 * @var Nette\DI\Container
	 */
	private $autowirePropertiesLocator;



	/**
	 * @param \Nette\DI\Container $dic
	 * @throws MemberAccessException
	 * @throws MissingServiceException
	 * @throws InvalidStateException
	 * @throws UnexpectedValueException
	 */
	public function injectProperties(Nette\DI\Container $dic)
	{
		if (!$this instanceof Nette\Application\UI\PresenterComponent && !$this instanceof Nette\Application\UI\Component) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of PresenterComponent.');
		}

		$this->autowirePropertiesLocator = $dic;

		$storage = $dic->hasService('autowired.cacheStorage')
			? $dic->getService('autowired.cacheStorage')
			: $dic->getByType('Nette\Caching\IStorage');
		$cache = new Nette\Caching\Cache($storage, 'Kdyby.Autowired.AutowireProperties');

		$containerFileName = ClassType::from($this->autowirePropertiesLocator)->getFileName();
		$cacheKey = [$presenterClass = get_class($this), $containerFileName];

		if (is_array($this->autowireProperties = $cache->load($cacheKey))) {
			foreach ($this->autowireProperties as $propName => $tmp) {
				unset($this->{$propName});
			}

			return;
		}

		$this->autowireProperties = [];

		$ignore = class_parents('Nette\Application\UI\Presenter') + ['ui' => 'Nette\Application\UI\Presenter'];
		$rc = new ClassType($this);
		foreach ($rc->getProperties() as $prop) {
			if (!$this->validateProperty($prop, $ignore)) {
				continue;
			}

			$this->resolveProperty($prop);
		}

		$files = array_map(function ($class) {
			return ClassType::from($class)->getFileName();
		}, array_diff(array_values(class_parents($presenterClass) + ['me' => $presenterClass]), $ignore));

		$files[] = $containerFileName;

		$cache->save($cacheKey, $this->autowireProperties, [
			$cache::FILES => $files,
		]);
	}



	private function validateProperty(Property $property, array $ignore)
	{
		if (in_array($property->getDeclaringClass()->getName(), $ignore, TRUE)) {
			return FALSE;
		}

		foreach ($property->getAnnotations() as $name => $value) {
			if (!in_array(Strings::lower($name), ['autowire', 'autowired'], TRUE)) {
				continue;
			}

			if (Strings::lower($name) !== $name || $name !== 'autowire') {
				throw new UnexpectedValueException("Annotation @$name on $property should be fixed to lowercase @autowire.", $property);
			}

			if ($property->isPrivate()) {
				throw new MemberAccessException("Autowired properties must be protected or public. Please fix visibility of $property or remove the @autowire annotation.", $property);
			}

			return TRUE;
		}

		return FALSE;
	}



	/**
	 * @param string $type
	 * @return string|bool
	 */
	private function findByTypeForProperty($type)
	{
		if (method_exists($this->autowirePropertiesLocator, 'findByType')) {
			$found = $this->autowirePropertiesLocator->findByType($type);

			return reset($found);
		}

		$type = ltrim(strtolower($type), '\\');

		return !empty($this->autowirePropertiesLocator->classes[$type])
			? $this->autowirePropertiesLocator->classes[$type]
			: FALSE;
	}



	/**
	 * @param Property $prop
	 * @throws MissingServiceException
	 * @throws UnexpectedValueException
	 */
	private function resolveProperty(Property $prop)
	{
		$type = $this->resolveAnnotationClass($prop, $prop->getAnnotation('var'), 'var');
		$metadata = [
			'value' => NULL,
			'type' => $type,
		];

		if (($args = (array) $prop->getAnnotation('autowire')) && !empty($args['factory'])) {
			$factoryType = $this->resolveAnnotationClass($prop, $args['factory'], 'autowire');

			if (!$this->findByTypeForProperty($factoryType)) {
				throw new MissingServiceException("Factory of type \"$factoryType\" not found for $prop in annotation @autowire.", $prop);
			}

			$factoryMethod = Method::from($factoryType, 'create');
			$createsType = $this->resolveAnnotationClass($factoryMethod, $factoryMethod->getAnnotation('return'), 'return');
			if ($createsType !== $type) {
				throw new UnexpectedValueException("The property $prop requires $type, but factory of type $factoryType, that creates $createsType was provided.", $prop);
			}

			unset($args['factory']);
			$metadata['arguments'] = array_values($args);
			$metadata['factory'] = $this->findByTypeForProperty($factoryType);

		} elseif (!$this->findByTypeForProperty($type)) {
			throw new MissingServiceException("Service of type \"$type\" not found for $prop in annotation @var.", $prop);
		}

		// unset property to pass control to __set() and __get()
		unset($this->{$prop->getName()});
		$this->autowireProperties[$prop->getName()] = $metadata;
	}



	private function resolveAnnotationClass(\Reflector $prop, $annotationValue, $annotationName)
	{
		/** @var Property|Method $prop */

		if (!$type = ltrim($annotationValue, '\\')) {
			throw new InvalidStateException("Missing annotation @{$annotationName} with typehint on {$prop}.", $prop);
		}

		if (!class_exists($type) && !interface_exists($type)) {
			if (substr(func_get_arg(1), 0, 1) === '\\') {
				throw new MissingClassException("Class \"$type\" was not found, please check the typehint on {$prop} in annotation @{$annotationName}.", $prop);
			}
			$expandedType = NULL;
			if (method_exists('Nette\Reflection\AnnotationsParser', 'expandClassName')) {
				$expandedType = Nette\Reflection\AnnotationsParser::expandClassName($annotationValue,
					$prop instanceof \ReflectionProperty
						? Nette\Reflection\Helpers::getDeclaringClass($prop)
						: $prop->getDeclaringClass()
				);
			}

			if ($expandedType && (class_exists($expandedType) || interface_exists($expandedType))) {
				$type = $expandedType;

			} elseif(!class_exists($type = $prop->getDeclaringClass()->getNamespaceName() . '\\' . $type) && !interface_exists($type)) {
				throw new MissingClassException("Neither class \"" . func_get_arg(1) . "\" or \"{$type}\" was found, please check the typehint on {$prop} in annotation @{$annotationName}.", $prop);
			}
		}

		return ClassType::from($type)->getName();
	}



	/**
	 * @param string $name
	 * @param mixed $value
	 * @throws MemberAccessException
	 * @return mixed
	 */
	public function __set($name, $value)
	{
		if (!isset($this->autowireProperties[$name])) {
			return parent::__set($name, $value);

		} elseif ($this->autowireProperties[$name]['value']) {
			throw new MemberAccessException("Property \$$name has already been set.");

		} elseif (!$value instanceof $this->autowireProperties[$name]['type']) {
			throw new MemberAccessException("Property \$$name must be an instance of " . $this->autowireProperties[$name]['type'] . ".");
		}

		return $this->autowireProperties[$name]['value'] = $value;
	}



	/**
	 * @param $name
	 * @throws MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		if (!isset($this->autowireProperties[$name])) {
			return parent::__get($name);
		}

		if (empty($this->autowireProperties[$name]['value'])) {
			if (!empty($this->autowireProperties[$name]['factory'])) {
				$factory = Callback::closure($this->autowirePropertiesLocator->getService($this->autowireProperties[$name]['factory']), 'create');
				$this->autowireProperties[$name]['value'] = Callback::invokeArgs($factory, $this->autowireProperties[$name]['arguments']);

			} else {
				$this->autowireProperties[$name]['value'] = $this->autowirePropertiesLocator->getByType($this->autowireProperties[$name]['type']);
			}
		}

		return $this->autowireProperties[$name]['value'];
	}

}
