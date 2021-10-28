<?php
declare(strict_types=1);

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
	 * @var array<array{"type": class-string, "factory"?: class-string, "arguments"?: array<mixed>}>
	 */
	private array $autowirePropertiesMeta = [];

	/**
	 * @var array<string, object>
	 */
	private array $autowireProperties = [];

	private Nette\DI\Container $autowirePropertiesLocator;

	/**
	 * @throws MemberAccessException
	 * @throws MissingServiceException
	 * @throws InvalidStateException
	 * @throws UnexpectedValueException
	 * @internal
	 */
	public function injectProperties(Nette\DI\Container $dic): void
	{
		if (! $this instanceof Nette\Application\UI\Component) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of ' . Nette\Application\UI\Component::class . '.');
		}

		$this->autowirePropertiesLocator = $dic;

		/** @var Nette\Caching\IStorage $storage */
		$storage = $dic->hasService('autowired.cacheStorage')
			? $dic->getService('autowired.cacheStorage')
			: $dic->getByType('Nette\Caching\IStorage');
		$cache = new Nette\Caching\Cache($storage, 'Kdyby.Autowired.AutowireProperties');

		$containerFileName = (new \ReflectionClass($this->autowirePropertiesLocator))->getFileName();
		/** @var class-string<self> $presenterClass */
		$presenterClass = static::class;
		$cacheKey = [$presenterClass, $containerFileName];

		$metadata = $cache->load($cacheKey);
		if (is_array($metadata)) {
			$this->autowirePropertiesMeta = $metadata;
			foreach ($this->autowirePropertiesMeta as $propName => $tmp) {
				unset($this->{$propName});
			}
			return;
		}

		$nettePresenterParents = class_parents(Nette\Application\UI\Presenter::class);
		assert(is_array($nettePresenterParents));
		$ignore = $nettePresenterParents + ['ui' => Nette\Application\UI\Presenter::class];
		$rc = new \ReflectionClass($presenterClass);
		foreach ($rc->getProperties() as $prop) {
			if (! $this->validateProperty($prop, $ignore)) {
				continue;
			}

			$this->resolveProperty($prop);
		}

		$presenterParents = class_parents($presenterClass);
		assert(is_array($presenterParents));
		$files = array_map(fn ($class) => (new \ReflectionClass($class))->getFileName(), array_diff(array_values($presenterParents + ['me' => $presenterClass]), $ignore));

		$files[] = $containerFileName;

		$cache->save($cacheKey, $this->autowirePropertiesMeta, [
			$cache::FILES => $files,
		]);
	}

	/**
	 * @param \ReflectionProperty $property
	 * @param array<string> $ignore
	 */
	private function validateProperty(\ReflectionProperty $property, array $ignore): bool
	{
		if (in_array($property->getDeclaringClass()->getName(), $ignore, TRUE)) {
			return FALSE;
		}

		foreach (PhpDocParser::parseComment((string) $property->getDocComment()) as $name => $value) {
			if (! in_array(Strings::lower($name), ['autowire', 'autowired'], TRUE)) {
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
	 * @template T of object
	 * @param class-string<T> $type
	 * @return T
	 */
	private function getAutowiredService(string $type, string $subject, \ReflectionProperty $property): object
	{
		try {
			return $this->autowirePropertiesLocator->getByType($type, TRUE);
		} catch (Nette\DI\MissingServiceException $exception) {
			$message = sprintf(
				'Unable to autowire %s for %s: %s',
				$subject,
				Reflection::toString($property),
				$exception->getMessage(),
			);
			throw new MissingServiceException($message, $property, $exception);
		}
	}

	/**
	 * @throws MissingServiceException
	 * @throws UnexpectedValueException
	 */
	private function resolveProperty(\ReflectionProperty $prop): void
	{
		$type = $this->resolvePropertyType($prop);
		$metadata = [
			'type' => $type,
		];

		$annotations = PhpDocParser::parseComment((string) $prop->getDocComment());
		$args = (array) end($annotations['autowire']);

		if (array_key_exists('factory', $args)) {
			$factoryType = $this->resolveFactoryType($prop, $args['factory'], 'autowire');
			unset($args['factory']);
			$arguments = array_values($args);

			$factory = $this->getAutowiredService($factoryType, 'service factory', $prop);
			if (! method_exists($factory, 'create')) {
				throw new InvalidStateException(sprintf('Service factory %s for property %s is missing create() method.', $factoryType, Reflection::toString($prop)), $prop);
			}
			$service = $factory->create(...$arguments);
			$createsType = is_object($service) ? get_class($service) : gettype($service);

			if ($createsType !== $type) {
				throw new UnexpectedValueException(sprintf('The property %s requires %s, but factory of type %s, that creates %s was provided.', Reflection::toString($prop), $type, $factoryType, $createsType), $prop);
			}
			$metadata['arguments'] = $arguments;
			$metadata['factory'] = $factoryType;

		} else {
			$this->getAutowiredService($type, 'service', $prop);
		}

		// unset property to pass control to __set() and __get()
		unset($this->{$prop->getName()});
		$this->autowirePropertiesMeta[$prop->getName()] = $metadata;
	}

	/**
	 * @return class-string
	 */
	private function resolvePropertyType(\ReflectionProperty $prop): string
	{
		$type = Reflection::getPropertyType($prop);
		if ($type === NULL) {
			$varType = Nette\DI\Helpers::parseAnnotation($prop, 'var');
			if ($varType !== NULL && $varType !== '') {
				$type = Reflection::expandClassName($varType, Reflection::getPropertyDeclaringClass($prop));
			}
		}

		if ($type === NULL) {
			throw new InvalidStateException(sprintf('Missing property typehint or annotation @var on %s.', Reflection::toString($prop)), $prop);
		}

		if (! class_exists($type) && ! interface_exists($type)) {
			throw new MissingClassException(sprintf('Class "%s" not found, please check the typehint on %s.', $type, Reflection::toString($prop)), $prop);
		}

		return $type;
	}

	/**
	 * @return class-string
	 */
	private function resolveFactoryType(\ReflectionProperty $prop, string $annotationValue, string $annotationName): string
	{
		$type = ltrim($annotationValue, '\\');
		if ($type === '') {
			throw new InvalidStateException(sprintf('Missing annotation @%s with typehint on %s.', $annotationName, Reflection::toString($prop)), $prop);
		}

		if (! class_exists($type) && ! interface_exists($type)) {
			if (substr($annotationValue, 0, 1) === '\\') {
				throw new MissingClassException(sprintf('Class "%s" was not found, please check the typehint on %s in annotation @%s.', $type, Reflection::toString($prop), $annotationName), $prop);
			}

			$expandedType = Reflection::expandClassName(
				$annotationValue,
				Reflection::getPropertyDeclaringClass($prop),
			);

			if ($expandedType && (class_exists($expandedType) || interface_exists($expandedType))) {
				$type = $expandedType;

			} else {
				$type = $prop->getDeclaringClass()->getNamespaceName() . '\\' . $type;
				if (! class_exists($type) && ! interface_exists($type)) {
					throw new MissingClassException(sprintf('Neither class "%s" or "%s" was found, please check the typehint on %s in annotation @%s.', $annotationValue, $type, Reflection::toString($prop), $annotationName), $prop);
				}
			}
		}

		return (new \ReflectionClass($type))->getName();
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @throws MemberAccessException
	 * @return void
	 */
	public function __set(string $name, $value): void
	{
		if (! isset($this->autowirePropertiesMeta[$name])) {
			parent::__set($name, $value);
			return;

		}

		if (isset($this->autowireProperties[$name])) {
			throw new MemberAccessException("Property \$$name has already been set.");

		}

		if (! $value instanceof $this->autowirePropertiesMeta[$name]['type']) {
			throw new MemberAccessException("Property \$$name must be an instance of " . $this->autowirePropertiesMeta[$name]['type'] . '.');
		}

		$this->autowireProperties[$name] = $value;
	}

	/**
	 * @throws MemberAccessException
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		if (! isset($this->autowirePropertiesMeta[$name])) {
			return parent::__get($name);
		}

		if (! isset($this->autowireProperties[$name])) {
			$this->autowireProperties[$name] = $this->createAutowiredPropertyService($name);
		}

		return $this->autowireProperties[$name];
	}

	private function createAutowiredPropertyService(string $name): object
	{
		if (array_key_exists('factory', $this->autowirePropertiesMeta[$name])) {
			/** @var class-string<object> $factoryType */
			$factoryType = $this->autowirePropertiesMeta[$name]['factory'];
			$arguments = $this->autowirePropertiesMeta[$name]['arguments'] ?? [];
			return $this->autowirePropertiesLocator->getByType($factoryType)->create(...$arguments);
		}

		/** @var class-string<object> $type */
		$type = $this->autowirePropertiesMeta[$name]['type'];
		return $this->autowirePropertiesLocator->getByType($type);
	}

}
