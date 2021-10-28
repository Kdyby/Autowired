<?php
declare(strict_types=1);

namespace KdybyTests;

use Nette\Configurator;
use Nette\DI\Container;
use Tester\TestCase;

abstract class ContainerTestCase extends TestCase
{

	protected function compileContainer(?string $configFile = NULL): Container
	{
		$configurator = new Configurator();
		$configurator->setTempDirectory(TEMP_DIR);
		$configurator->addParameters(['container' => ['class' => 'SystemContainer_' . md5(TEMP_DIR)]]);
		$configurator->addConfig(__DIR__ . '/config/application.neon');

		if ($configFile !== NULL) {
			$configurator->addConfig(__DIR__ . '/config/' . $configFile . '.neon');
		}

		return $configurator->createContainer();
	}

}
