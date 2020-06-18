<?php declare(strict_types=1);

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Autowired;

use Nette;
use Nette\Utils\Reflection;
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

		/** @var Nette\Caching\IStorage $storage */
		$storage = $dic->hasService('autowired.cacheStorage')
			? $dic->getService('autowired.cacheStorage')
			: $dic->getByType('Nette\Caching\IStorage');
		$cache = new Nette\Caching\Cache($storage, 'Kdyby.Autowired.AutowireProperties');

		$containerFileName = (new \ReflectionClass($this->autowirePropertiesLocator))->getFileName();
		$cacheKey = [$presenterClass = get_class($this), $containerFileName];

		if (is_array($this->autowireProperties = $cache->load($cacheKey))) {
			foreach ($this->autowireProperties as $propName => $tmp) {
				unset($this->{$propName});
			}

			return;
		}

		$this->autowireProperties = [];

		$ignore = class_parents('Nette\Application\UI\Presenter') + ['ui' => 'Nette\Application\UI\Presenter'];
		$rc = new \ReflectionClass($this);
		foreach ($rc->getProperties() as $prop) {
			if (!$this->validateProperty($prop, $ignore)) {
				continue;
			}

			$this->resolveProperty($prop);
		}

		$files = array_map(function ($class) {
			return (new \ReflectionClass($class))->getFileName();
		}, array_diff(array_values(class_parents($presenterClass) + ['me' => $presenterClass]), $ignore));

		$files[] = $containerFileName;

		$cache->save($cacheKey, $this->autowireProperties, [
			$cache::FILES => $files,
		]);
	}



	private function validateProperty(\ReflectionProperty $property, array $ignore): bool
	{
		if (in_array($property->getDeclaringClass()->getName(), $ignore, TRUE)) {
			return FALSE;
		}

		foreach (PhpDocParser::parseComment((string) $property->getDocComment()) as $name => $value) {
			if (!in_array(Strings::lower($name), ['autowire', 'autowired'], TRUE)) {
				continue;
			}

			if (Strings::lower($name) !== $name || $name !== 'autowire') {
				throw new UnexpectedValueException(sprintf('Annotation @%s on %s should be fixed to lowercase @autowire.', $name, Reflection::toString($property)), $property);
			}

			if ($property->isPrivate()) {
				throw new MemberAccessException(sprintf('Autowired properties must be protected or public. Please fix visibility of %s or remove the @autowire annotation.', Reflection::toString($property)), $property);
			}

			return TRUE;
		}

		return FALSE;
	}



	/**
	 * @return string|bool
	 */
	private function findByTypeForProperty(string $type)
	{
		$found = $this->autowirePropertiesLocator->findByType($type);
		return reset($found);
	}



	/**
	 * @throws MissingServiceException
	 * @throws UnexpectedValueException
	 */
	private function resolveProperty(\ReflectionProperty $prop): void
	{
		$type = $this->resolvePropertyType($prop);
		$metadata = [
			'value' => NULL,
			'type' => $type,
		];

		$annotations = PhpDocParser::parseComment((string) $prop->getDocComment());
		$args = (array) end($annotations['autowire']);

		if (array_key_exists('factory', $args)) {
			$factoryType = $this->resolveFactoryType($prop, $args['factory'], 'autowire');

			if (!$this->findByTypeForProperty($factoryType)) {
				throw new MissingServiceException(sprintf('Factory of type "%s" not found for %s in annotation @autowire.', $factoryType, Reflection::toString($prop)), $prop);
			}

			$factoryMethod = new \ReflectionMethod($factoryType, 'create');
			$createsType = $this->resolveReturnType($factoryMethod);
			if ($createsType !== $type) {
				throw new UnexpectedValueException(sprintf('The property %s requires %s, but factory of type %s, that creates %s was provided.', Reflection::toString($prop), $type, $factoryType, $createsType), $prop);
			}

			unset($args['factory']);
			$metadata['arguments'] = array_values($args);
			$metadata['factory'] = $this->findByTypeForProperty($factoryType);

		} elseif (!$this->findByTypeForProperty($type)) {
			throw new MissingServiceException(sprintf('Service of type "%s" not found for %s in annotation @var.', $type, Reflection::toString($prop)), $prop);
		}

		// unset property to pass control to __set() and __get()
		unset($this->{$prop->getName()});
		$this->autowireProperties[$prop->getName()] = $metadata;
	}



	private function resolvePropertyType(\ReflectionProperty $prop): string
	{
		if ($type = Reflection::getPropertyType($prop)) {
		} elseif ($type = Nette\DI\Helpers::parseAnnotation($prop, 'var')) {
			$type = Reflection::expandClassName($type, Reflection::getPropertyDeclaringClass($prop));
		} else {
			throw new InvalidStateException(sprintf('Missing property typehint or annotation @var on %s.', Reflection::toString($prop)), $prop);
		}

		if (!class_exists($type) && !interface_exists($type)) {
			throw new MissingClassException(sprintf('Class "%s" not found, please check the typehint on %s.', $type, Reflection::toString($prop)), $prop);
		}

		return $type;
	}



	private function resolveReturnType(\ReflectionMethod $method): string
	{
		$type = Nette\DI\Helpers::getReturnType($method);
		if (!class_exists($type) && !interface_exists($type)) {
			throw new MissingClassException(sprintf('Class "%s" not found, please check the typehint on %s.', $type, Reflection::toString($method)), $method);
		}
		return $type;
	}



	private function resolveFactoryType(\ReflectionProperty $prop, string $annotationValue, string $annotationName): string
	{
		if (!$type = ltrim($annotationValue, '\\')) {
			throw new InvalidStateException(sprintf('Missing annotation @%s with typehint on %s.', $annotationName, Reflection::toString($prop)), $prop);
		}

		if (!class_exists($type) && !interface_exists($type)) {
			if (substr(func_get_arg(1), 0, 1) === '\\') {
				throw new MissingClassException(sprintf('Class "%s" was not found, please check the typehint on %s in annotation @%s.', $type, Reflection::toString($prop), $annotationName), $prop);
			}

			$expandedType = Reflection::expandClassName(
				$annotationValue,
				Reflection::getPropertyDeclaringClass($prop)
			);

			if ($expandedType && (class_exists($expandedType) || interface_exists($expandedType))) {
				$type = $expandedType;

			} elseif(!class_exists($type = $prop->getDeclaringClass()->getNamespaceName() . '\\' . $type) && !interface_exists($type)) {
				throw new MissingClassException(sprintf('Neither class "%s" or "%s" was found, please check the typehint on %s in annotation @%s.', func_get_arg(1), $type, Reflection::toString($prop), $annotationName), $prop);
			}
		}

		return (new \ReflectionClass($type))->getName();
	}



	/**
	 * @param mixed $value
	 * @throws MemberAccessException
	 * @return mixed
	 */
	public function __set(string $name, $value)
	{
		if (!isset($this->autowireProperties[$name])) {
			parent::__set($name, $value);
			return;

		}

		if ($this->autowireProperties[$name]['value']) {
			throw new MemberAccessException("Property \$$name has already been set.");

		}

		if (!$value instanceof $this->autowireProperties[$name]['type']) {
			throw new MemberAccessException("Property \$$name must be an instance of " . $this->autowireProperties[$name]['type'] . ".");
		}

		return $this->autowireProperties[$name]['value'] = $value;
	}



	/**
	 * @throws MemberAccessException
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		if (!isset($this->autowireProperties[$name])) {
			return parent::__get($name);
		}

		if ($this->autowireProperties[$name]['value'] == null) { // intentionally ==
			if (array_key_exists('factory', $this->autowireProperties[$name])) {
				$this->autowireProperties[$name]['value'] = $this->autowirePropertiesLocator->getService($this->autowireProperties[$name]['factory'])->create(...$this->autowireProperties[$name]['arguments']);

			} else {
				$this->autowireProperties[$name]['value'] = $this->autowirePropertiesLocator->getByType($this->autowireProperties[$name]['type']);
			}
		}

		return $this->autowireProperties[$name]['value'];
	}

}
