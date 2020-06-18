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

		if ($cache->load($presenterClass = get_class($this)) !== NULL) {
			return;
		}

		$ignore = class_parents('Nette\Application\UI\Presenter') + ['ui' => 'Nette\Application\UI\Presenter'];
		$rc = new \ReflectionClass($this);
		foreach ($rc->getMethods() as $method) {
			if (in_array($method->getDeclaringClass()->getName(), $ignore, TRUE) || !Strings::startsWith($method->getName(), 'createComponent')) {
				continue;
			}

			foreach ($method->getParameters() as $parameter) {
				if ($parameter->getClass() === NULL || !$class = $parameter->getClass()->getName()) { // has object type hint
					continue;
				}

				if (!$this->findByTypeForFactory($class) && !$parameter->allowsNull()) {
					throw new MissingServiceException(sprintf('No service of type %s found. Make sure the type hint in %s() is written correctly and service of this type is registered.', $class, Reflection::toString($method)));
				}
			}
		}

		$files = array_map(function ($class) {
			return (new \ReflectionClass($class))->getFileName();
		}, array_diff(array_values(class_parents($presenterClass) + ['me' => $presenterClass]), $ignore));

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
			if ($first !== false && !($first->getClass() && $first->getClass()->getName())) {
				$args[] = $name;
			}

			$args = Nette\DI\Resolver::autowireArguments($methodReflection, $args, $getter);
			$component = $this->{$method}(...$args);
			if (!$component instanceof Nette\ComponentModel\IComponent && !isset($this->components[$name])) {
				throw new Nette\UnexpectedValueException(sprintf('Method %s() did not return or create the desired component.', Reflection::toString($methodReflection)));
			}

			return $component;
		}

		return null;
	}

}
