<?php declare(strict_types=1);

namespace KdybyTests;

use Nette\Configurator;
use Nette\DI\Container;

abstract class ContainerTestCase extends \Tester\TestCase
{

	protected function compileContainer(?string $configFile = null): Container
	{
		$configurator = new Configurator;
		$configurator->setTempDirectory(TEMP_DIR);
		$configurator->addParameters(['container' => ['class' => 'SystemContainer_'.md5(TEMP_DIR)]]);
		$configurator->addConfig(__DIR__ . '/config/application.neon');

		if ($configFile !== null) {
			$configurator->addConfig(__DIR__ . '/config/' . $configFile . '.neon');
		}

		return $configurator->createContainer();
	}

}
