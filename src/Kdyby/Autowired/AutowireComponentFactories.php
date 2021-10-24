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
		if (!$this instanceof Nette\Application\UI\PresenterComponent && !$this instanceof Nette\Application\UI\Component) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of PresenterComponent.');
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

			foreach ($method->getParameters() as $parameter) {
				$parameterType = $parameter->getType();
				if (! $parameterType instanceof \ReflectionNamedType || $parameterType->isBuiltin()) { // has object type hint
					continue;
				}

				$class = $parameterType->getName();

				if (!$this->findByTypeForFactory($class) && !$parameter->allowsNull()) {
					throw new MissingServiceException(sprintf('No service of type %s found. Make sure the type hint in %s is written correctly and service of this type is registered.', $class, Reflection::toString($method)));
				}
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
	 * @return string|bool
	 */
	private function findByTypeForFactory(string $type)
	{
		$found = $this->autowireComponentFactoriesLocator->findByType($type);
		return reset($found);
	}



	/**
	 * @throws Nette\UnexpectedValueException
	 */
	protected function createComponent(string $name): ?IComponent
	{
		$getter = function (string $type) {
			/** @var class-string<object> $type */
			return $this->getComponentFactoriesLocator()->getByType($type);
		};

		$ucName = ucfirst($name);
		$method = 'createComponent' . $ucName;
		if ($ucName !== $name && method_exists($this, $method)) {
			$methodReflection = new \ReflectionMethod($this, $method);
			if ($methodReflection->getName() !== $method) {
				return null;
			}
			$parameters = $methodReflection->getParameters();

			$args = [];
			$first = reset($parameters);
			if ($first !== false) {
				$parameterType = $first->getType();
				if (! $parameterType instanceof \ReflectionNamedType || $parameterType->isBuiltin()) {
					$args[] = $name;
				}
			}

			$args = Nette\DI\Resolver::autowireArguments($methodReflection, $args, $getter);
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

}
