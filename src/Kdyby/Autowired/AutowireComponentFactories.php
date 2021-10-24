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
use Nette\ComponentModel\IComponent;
use Nette\Utils\Reflection;
use Nette\Utils\Strings;



/**
 * @author matej21 <matej21@matej21.cz>
 * @author Filip Procházka <filip@prochazka.su>
 *
 * @method Nette\Application\UI\Presenter getPresenter()
 */
trait AutowireComponentFactories
{

	/**
	 * @var Nette\DI\Container
	 */
	private $autowireComponentFactoriesLocator;



	protected function getComponentFactoriesLocator(): Nette\DI\Container
	{
		if ($this->autowireComponentFactoriesLocator === NULL) {
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
		if (!$this instanceof Nette\Application\UI\Component) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of ' . Nette\Application\UI\Component::class . '.');
		}

		$this->autowireComponentFactoriesLocator = $dic;

		/** @var Nette\Caching\IStorage $storage */
		$storage = $dic->hasService('autowired.cacheStorage')
			? $dic->getService('autowired.cacheStorage')
			: $dic->getByType('Nette\Caching\IStorage');
		$cache = new Nette\Caching\Cache($storage, 'Kdyby.Autowired.AutowireComponentFactories');

		/** @var class-string<self> $presenterClass */
		$presenterClass = get_class($this);
		if ($cache->load($presenterClass) !== NULL) {
			return;
		}

		$nettePresenterParents = class_parents(Nette\Application\UI\Presenter::class);
		assert(is_array($nettePresenterParents));
		$ignore = $nettePresenterParents + ['ui' => Nette\Application\UI\Presenter::class];
		$rc = new \ReflectionClass($presenterClass);
		foreach ($rc->getMethods() as $method) {
			if (in_array($method->getDeclaringClass()->getName(), $ignore, TRUE) || !Strings::startsWith($method->getName(), 'createComponent')) {
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
		$files = array_map(function ($class) {
			return (new \ReflectionClass($class))->getFileName();
		}, array_diff(array_values($presenterParents + ['me' => $presenterClass]), $ignore));

		$files[] = (new \ReflectionClass($this->autowireComponentFactoriesLocator))->getFileName();

		$cache->save($presenterClass, TRUE, [
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
				return null;
			}

			$args = $this->resolveMethodArguments($methodReflection, $name);
			$component = $this->{$method}(...$args);
			if ($component instanceof IComponent) {
				return $component;
			}
			$components = iterator_to_array($this->getComponents());
			if (!isset($components[$name])) {
				throw new Nette\UnexpectedValueException(sprintf('Method %s did not return or create the desired component.', Reflection::toString($methodReflection)));
			}
		}

		return null;
	}


	/**
	 * @return array<int, mixed>
	 */
	private function resolveMethodArguments(\ReflectionMethod $method, string $componentName = 'componentName'): array
	{
		$getter = function (string $type): object {
			/** @var class-string<object> $type */
			return $this->getComponentFactoriesLocator()->getByType($type);
		};
		$parameters = $method->getParameters();

		$args = [];
		$first = reset($parameters);
		if ($first !== false) {
			$parameterType = Nette\Utils\Type::fromReflection($first);
			if ($parameterType === null || $parameterType->allows('string')) {
				$args[] = $componentName;
			}
		}

		return Nette\DI\Resolver::autowireArguments($method, $args, $getter);
	}

}
