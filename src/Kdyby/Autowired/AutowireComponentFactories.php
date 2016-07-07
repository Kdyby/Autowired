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
use Nette\Reflection\ClassType;
use Nette\Reflection\Method;
use Nette\Reflection\Property;
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



	/**
	 * @return Nette\DI\Container
	 */
	protected function getComponentFactoriesLocator()
	{
		if ($this->autowireComponentFactoriesLocator === NULL) {
			$this->injectComponentFactories($this->getPresenter()->getContext());
		}

		return $this->autowireComponentFactoriesLocator;
	}



	/**
	 * @param \Nette\DI\Container $dic
	 * @throws MemberAccessException
	 * @internal
	 */
	public function injectComponentFactories(Nette\DI\Container $dic)
	{
		if (!$this instanceof Nette\Application\UI\PresenterComponent && !$this instanceof Nette\Application\UI\Component) {
			throw new MemberAccessException('Trait ' . __TRAIT__ . ' can be used only in descendants of PresenterComponent.');
		}

		$this->autowireComponentFactoriesLocator = $dic;

		$storage = $dic->hasService('autowired.cacheStorage')
			? $dic->getService('autowired.cacheStorage')
			: $dic->getByType('Nette\Caching\IStorage');
		$cache = new Nette\Caching\Cache($storage, 'Kdyby.Autowired.AutowireComponentFactories');

		if ($cache->load($presenterClass = get_class($this)) !== NULL) {
			return;
		}

		$ignore = class_parents('Nette\Application\UI\Presenter') + ['ui' => 'Nette\Application\UI\Presenter'];
		$rc = new ClassType($this);
		foreach ($rc->getMethods() as $method) {
			if (in_array($method->getDeclaringClass()->getName(), $ignore, TRUE) || !Strings::startsWith($method->getName(), 'createComponent')) {
				continue;
			}

			foreach ($method->getParameters() as $parameter) {
				if (!$class = $parameter->getClassName()) { // has object type hint
					continue;
				}

				if (!$this->findByTypeForFactory($class) && !$parameter->allowsNull()) {
					throw new MissingServiceException("No service of type {$class} found. Make sure the type hint in $method is written correctly and service of this type is registered.");
				}
			}
		}

		$files = array_map(function ($class) {
			return ClassType::from($class)->getFileName();
		}, array_diff(array_values(class_parents($presenterClass) + ['me' => $presenterClass]), $ignore));

		$files[] = ClassType::from($this->autowireComponentFactoriesLocator)->getFileName();

		$cache->save($presenterClass, TRUE, [
			$cache::FILES => $files,
		]);
	}



	/**
	 * @param string $type
	 * @return string|bool
	 */
	private function findByTypeForFactory($type)
	{
		if (method_exists($this->autowireComponentFactoriesLocator, 'findByType')) {
			$found = $this->autowireComponentFactoriesLocator->findByType($type);

			return reset($found);
		}

		$type = ltrim(strtolower($type), '\\');

		return !empty($this->autowireComponentFactoriesLocator->classes[$type])
			? $this->autowireComponentFactoriesLocator->classes[$type]
			: FALSE;
	}



	/**
	 * @param $name
	 * @return Nette\ComponentModel\IComponent
	 * @throws Nette\UnexpectedValueException
	 */
	protected function createComponent($name)
	{
		$sl = $this->getComponentFactoriesLocator();

		$ucName = ucfirst($name);
		$method = 'createComponent' . $ucName;
		if ($ucName !== $name && method_exists($this, $method)) {
			$methodReflection = new Method($this, $method);
			if ($methodReflection->getName() !== $method) {
				return;
			}
			$parameters = $methodReflection->getParameters();

			$args = [];
			if (($first = reset($parameters)) && !$first->className) {
				$args[] = $name;
			}

			$args = Nette\DI\Helpers::autowireArguments($methodReflection, $args, $sl);
			$component = call_user_func_array([$this, $method], $args);
			if (!$component instanceof Nette\ComponentModel\IComponent && !isset($this->components[$name])) {
				throw new Nette\UnexpectedValueException("Method $methodReflection did not return or create the desired component.");
			}

			return $component;
		}
	}

}
