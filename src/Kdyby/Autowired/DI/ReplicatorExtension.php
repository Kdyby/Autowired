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



if (!class_exists('Nette\DI\CompilerExtension')) {
	class_alias('Nette\Config\CompilerExtension', 'Nette\DI\CompilerExtension');
	class_alias('Nette\Config\Configurator', 'Nette\Configurator');
	class_alias('Nette\Config\Compiler', 'Nette\DI\Compiler');
	class_alias('Nette\Config\Helpers', 'Nette\DI\Config\Helpers');
}

/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowiredExtension extends Nette\DI\CompilerExtension
{

	public function afterCompile(Code\ClassType $class)
	{
		$initialize = $class->methods['initialize'];
		$container = $this->getContainerBuilder();

		$initialize->addBody($container->formatPhp(
			'Nette\Diagnostics\Debugger::$blueScreen->addPanel(?);',
			Nette\Config\Compiler::filterArguments(array('Kdyby\Autowired\Diagnostics\Panel::renderException'))
		));
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
