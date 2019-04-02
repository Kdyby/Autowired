<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.txt that was distributed with this source code.
 */

namespace Kdyby\Autowired\DI;

use Kdyby;
use Nette;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowiredExtension extends Nette\DI\CompilerExtension
{

	public $defaults = [
		'cacheStorage' => '@Nette\Caching\IStorage',
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = Nette\DI\Config\Helpers::merge($this->getConfig(), $this->defaults);

		$storage = $builder->addDefinition($this->prefix('cacheStorage'))
			->setType('Nette\Caching\IStorage')
			->setAutowired(FALSE);

		$storage->factory = is_string($config['cacheStorage'])
			? new Nette\DI\Definitions\Statement($config['cacheStorage'])
			: $config['cacheStorage'];
	}



	public function afterCompile(Code\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$initialize->addBody('Kdyby\Autowired\Diagnostics\Panel::registerBluescreen();');
	}



	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('autowired', new AutowiredExtension());
		};
	}

}
