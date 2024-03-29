<?php
declare(strict_types=1);

namespace Kdyby\Autowired;

use Kdyby\Autowired\Attributes\Autowire;
use Kdyby\Autowired\Caching\CacheFactory;
use Nette;
use Nette\Utils\Reflection;
use Nette\Utils\Type;


/**
 * @author Filip Procházka <filip@prochazka.su>
 */
trait AutowireProperties
{

	/**
	 * @var array<array{"type": class-string}|array{"type": class-string, "factory": class-string, "arguments": array<mixed>}>
	 */
	private array $autowirePropertiesMeta = [];

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

		$cache = $dic->getByType(CacheFactory::class)->create(static::class, 'Kdyby.Autowired.AutowireProperties');

		$metadata = $cache->load();
		if (is_array($metadata)) {
			$this->autowirePropertiesMeta = $metadata;
			foreach ($this->autowirePropertiesMeta as $propName => $tmp) {
				unset($this->{$propName});
			}
			return;
		}

		$rc = new \ReflectionClass($this);
		foreach ($rc->getProperties() as $prop) {
			if (is_a(Nette\Application\UI\Presenter::class, $prop->getDeclaringClass()->getName(), TRUE)) {
				continue;
			}

			$this->resolveProperty($prop);
		}

		$cache->save($this->autowirePropertiesMeta);
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
		$metadata = $this->resolveAutowireMetadata($prop);
		if ($metadata === NULL) {
			return;
		}

		if (isset($metadata['factory']) && isset($metadata['arguments'])) {
			$factory = $this->getAutowiredService($metadata['factory'], 'service factory', $prop);
			if (! method_exists($factory, 'create')) {
				throw new InvalidStateException(sprintf('Service factory %s for property %s is missing create() method.', $metadata['factory'], Reflection::toString($prop)), $prop);
			}
			$service = $factory->create(...$metadata['arguments']);
			$createsType = is_object($service) ? $service::class : gettype($service);

			if ($createsType !== $metadata['type']) {
				throw new UnexpectedValueException(sprintf('The property %s requires %s, but factory of type %s, that creates %s was provided.', Reflection::toString($prop), $metadata['type'], $metadata['factory'], $createsType), $prop);
			}

		} else {
			$this->getAutowiredService($metadata['type'], 'service', $prop);
		}

		// unset property to pass control to __get()
		unset($this->{$prop->getName()});
		$this->autowirePropertiesMeta[$prop->getName()] = $metadata;
	}

	/**
	 * @return array{"type": class-string}|array{"type": class-string, "factory": class-string, "arguments": array<mixed>}|NULL
	 */
	private function resolveAutowireMetadata(\ReflectionProperty $property): ?array
	{
		$attributes = $property->getAttributes(Autowire::class);
		if (count($attributes) === 0) {
			return NULL;
		}

		if ($property->isPrivate()) {
			throw new MemberAccessException(sprintf('Autowired properties must be protected or public. Please fix visibility of %s or remove the Autowire attribute.', Reflection::toString($property)), $property);
		}

		/** @var Autowire $autowire */
		$autowire = reset($attributes)->newInstance();
		$metadata = $autowire->toArray();
		$metadata['type'] = $this->resolvePropertyType($property);

		return $metadata;
	}

	/**
	 * @return class-string
	 */
	private function resolvePropertyType(\ReflectionProperty $prop): string
	{
		$type = Type::fromReflection($prop);
		if ($type === NULL) {
			throw new InvalidStateException(sprintf('Missing property typehint on %s.', Reflection::toString($prop)), $prop);
		}
		if (! $type->isSingle()) {
			throw new InvalidStateException('The ' . Reflection::toString($prop) . ' is not expected to have a union or intersection type.', $prop);
		}

		$className = $type->getSingleName();
		assert(is_string($className));
		if (! class_exists($className) && ! interface_exists($className)) {
			throw new MissingClassException(sprintf('Class "%s" not found, please check the typehint on %s.', $type, Reflection::toString($prop)), $prop);
		}

		return $className;
	}

	public function &__get(string $name): mixed
	{
		if (! isset($this->autowirePropertiesMeta[$name])) {
			return parent::__get($name);
		}

		if (! isset($this->{$name})) {
			$this->{$name} = $this->createAutowiredPropertyService($name);
		}

		return $this->{$name};
	}

	public function __set(string $name, mixed $value): void
	{
		if (! isset($this->autowirePropertiesMeta[$name])) {
			parent::__set($name, $value);
			return;
		}

		// Assign directly bypassing magic from parents (e.g. SmartObject validation)
		$this->{$name} = $value;
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
