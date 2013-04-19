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



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait AutowireProperties
{

	/**
	 * @var array
	 */
	private $autowire = array();

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
		if (!$this instanceof Nette\Application\UI\PresenterComponent) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of PresenterComponent.');
		}

		$this->autowirePropertiesLocator = $dic;
		$cache = new Nette\Caching\Cache($dic->getByType('Nette\Caching\IStorage'), 'Kdyby.Autowired.PresenterComponent');
		if (($this->autowire = $cache->load($presenterClass = get_class($this))) === NULL) {
			$this->autowire = array();

			$rc = ClassType::from($this);
			$ignore = class_parents('Nette\Application\UI\Presenter') + array('ui' => 'Nette\Application\UI\Presenter');
			foreach ($rc->getProperties(Property::IS_PUBLIC | Property::IS_PROTECTED) as $prop) {
				/** @var Property $prop */
				if (in_array($prop->getDeclaringClass()->getName(), $ignore) || !$prop->hasAnnotation('autowire')) {
					continue;
				}

				$this->resolveProperty($prop);
			}

			$files = array_map(function ($class) {
				return ClassType::from($class)->getFileName();
			}, array_diff(array_values(class_parents($presenterClass) + array('me' => $presenterClass)), $ignore));

			$cache->save($presenterClass, $this->autowire, array(
				$cache::FILES => $files,
			));

		} else {
			foreach ($this->autowire as $propName => $tmp) {
				unset($this->{$propName});
			}
		}
	}



	/**
	 * @param Property $prop
	 * @throws MissingServiceException
	 * @throws UnexpectedValueException
	 */
	private function resolveProperty(Property $prop)
	{
		$type = $this->resolveAnnotationClass($prop, $prop->getAnnotation('var'), 'var');
		$metadata = array(
			'value' => NULL,
			'type' => $type,
		);

		if (($args = (array) $prop->getAnnotation('autowire')) && !empty($args['factory'])) {
			$factoryType = $this->resolveAnnotationClass($prop, $args['factory'], 'autowire');

			if (empty($this->autowirePropertiesLocator->classes[strtolower($factoryType)])) {
				throw new MissingServiceException("Factory of type \"$factoryType\" not found for $prop in annotation @autowire.");
			}

			$factoryMethod = Method::from($factoryType, 'create');
			$createsType = $this->resolveAnnotationClass($factoryMethod, $factoryMethod->getAnnotation('return'), 'return');
			if ($createsType !== $type) {
				throw new UnexpectedValueException("The property $prop requires $type, but factory of type $factoryType, that creates $createsType was provided.");
			}

			unset($args['factory']);
			$metadata['arguments'] = array_values($args);
			$metadata['factory'] = $this->autowirePropertiesLocator->classes[strtolower($factoryType)];

		} else {
			if (empty($this->autowirePropertiesLocator->classes[strtolower($type)])) {
				throw new MissingServiceException("Service of type \"$type\" not found for $prop in annotation @var.");
			}
		}

		// unset property to pass control to __set() and __get()
		unset($this->{$prop->getName()});
		$this->autowire[$prop->getName()] = $metadata;
	}



	private function resolveAnnotationClass(\Reflector $prop, $annotationValue, $annotationName)
	{
		/** @var Property|Method $prop */

		if (!$type = ltrim($annotationValue, '\\')) {
			throw new InvalidStateException("Missing annotation @{$annotationName} with typehint on {$prop}.");
		}

		if (!class_exists($type) && !interface_exists($type)) {
			if (substr(func_get_arg(1), 0, 1) === '\\') {
				throw new MissingClassException("Class \"$type\" was not found, please check the typehint on {$prop} in annotation @{$annotationName}");
			}

			if (!class_exists($type = $prop->getDeclaringClass()->getNamespaceName() . '\\' . $type) && !interface_exists($type)) {
				throw new MissingClassException("Neither class \"" . func_get_arg(1) . "\" or \"{$type}\" was found, please check the typehint on {$prop} in annotation @{$annotationName}");
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
		if (!isset($this->autowire[$name])) {
			return parent::__set($name, $value);

		} elseif ($this->autowire[$name]['value']) {
			throw new MemberAccessException("Property \$$name has already been set.");

		} elseif (!$value instanceof $this->autowire[$name]['type']) {
			throw new MemberAccessException("Property \$$name must be an instance of " . $this->autowire[$name]['type'] . ".");
		}

		return $this->autowire[$name]['value'] = $value;
	}



	/**
	 * @param $name
	 * @throws MemberAccessException
	 * @return mixed
	 */
	public function &__get($name)
	{
		if (!isset($this->autowire[$name])) {
			return parent::__get($name);
		}

		if (empty($this->autowire[$name]['value'])) {
			if (!empty($this->autowire[$name]['factory'])) {
				$factory = callback($this->autowirePropertiesLocator->getService($this->autowire[$name]['factory']), 'create');
				$this->autowire[$name]['value'] = $factory->invokeArgs($this->autowire[$name]['arguments']);

			} else {
				$this->autowire[$name]['value'] = $this->autowirePropertiesLocator->getByType($this->autowire[$name]['type']);
			}
		}

		return $this->autowire[$name]['value'];
	}

}
