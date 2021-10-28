<?php
declare(strict_types=1);

namespace Kdyby\Autowired;

use Nette;
use Nette\ComponentModel\IComponent;
use Nette\Utils\Reflection;
use Nette\Utils\Strings;



/**
 * @author matej21 <matej21@matej21.cz>
 * @author Filip Procházka <filip@prochazka.su>
 * @method Nette\Application\UI\Presenter getPresenter()
 */
trait AutowireComponentFactories
{

	private Nette\DI\Container $autowireComponentFactoriesLocator;

	protected function getComponentFactoriesLocator(): Nette\DI\Container
	{
		if (! isset($this->autowireComponentFactoriesLocator)) {
			$this->injectComponentFactories($this->getPresenter()->getContext());
		}

		return $this->autowireComponentFactoriesLocator;
	}

	/**
	 * @throws MemberAccessException
	 * @internal
	 */
	public function injectComponentFactories(Nette\DI\Container $dic): void
	{
		if (! $this instanceof Nette\Application\UI\Component) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of ' . Nette\Application\UI\Component::class . '.');
		}

		$this->autowireComponentFactoriesLocator = $dic;

		/** @var Nette\Caching\IStorage $storage */
		$storage = $dic->hasService('autowired.cacheStorage')
			? $dic->getService('autowired.cacheStorage')
			: $dic->getByType('Nette\Caching\IStorage');
		$cache = new Nette\Caching\Cache($storage, 'Kdyby.Autowired.AutowireComponentFactories');

		$containerFileName = (new \ReflectionClass($this->autowireComponentFactoriesLocator))->getFileName();
		/** @var class-string<self> $presenterClass */
		$presenterClass = static::class;
		$cacheKey = [$presenterClass, $containerFileName];

		if ($cache->load($cacheKey) !== NULL) {
			return;
		}

		$nettePresenterParents = class_parents(Nette\Application\UI\Presenter::class);
		assert(is_array($nettePresenterParents));
		$ignore = $nettePresenterParents + ['ui' => Nette\Application\UI\Presenter::class];
		$rc = new \ReflectionClass($presenterClass);
		foreach ($rc->getMethods() as $method) {
			if (in_array($method->getDeclaringClass()->getName(), $ignore, TRUE) || ! Strings::startsWith($method->getName(), 'createComponent')) {
				continue;
			}

			try {
				$this->resolveMethodArguments($method);
			} catch (Nette\DI\MissingServiceException | Nette\DI\ServiceCreationException $exception) {
				throw new MissingServiceException($exception->getMessage(), $method, $exception);
			}
		}

		$presenterParents = class_parents($presenterClass);
		assert(is_array($presenterParents));
		$files = array_map(fn ($class) => (new \ReflectionClass($class))->getFileName(), array_diff(array_values($presenterParents + ['me' => $presenterClass]), $ignore));

		$files[] = $containerFileName;

		$cache->save($cacheKey, TRUE, [
			$cache::FILES => $files,
		]);
	}

	/**
	 * @throws Nette\UnexpectedValueException
	 */
	protected function createComponent(string $name): ?IComponent
	{
		$ucName = ucfirst($name);
		$method = 'createComponent' . $ucName;
		if ($ucName !== $name && method_exists($this, $method)) {
			$methodReflection = new \ReflectionMethod($this, $method);
			if ($methodReflection->getName() !== $method) {
				return NULL;
			}

			$args = $this->resolveMethodArguments($methodReflection, $name);
			$component = $this->{$method}(...$args);
			if ($component instanceof IComponent) {
				return $component;
			}
			$components = iterator_to_array($this->getComponents());
			if (! isset($components[$name])) {
				throw new Nette\UnexpectedValueException(sprintf('Method %s did not return or create the desired component.', Reflection::toString($methodReflection)));
			}
		}

		return NULL;
	}

	/**
	 * @return array<int, mixed>
	 */
	private function resolveMethodArguments(\ReflectionMethod $method, string $componentName = 'componentName'): array
	{
		$getter = function (string $type): object {
			/** @var class-string<object> $type */
			$serviceLocator = $this->getComponentFactoriesLocator();
			return $serviceLocator->getByType($type);
		};
		$parameters = $method->getParameters();

		$args = [];
		$first = reset($parameters);
		if ($first !== FALSE) {
			$parameterType = Nette\Utils\Type::fromReflection($first);
			if ($parameterType === NULL || $parameterType->allows('string')) {
				$args[] = $componentName;
			}
		}

		return Nette\DI\Resolver::autowireArguments($method, $args, $getter);
	}

}
