<?php

/**
 * Test: Kdyby\Autowired\Integration.
 *
 * @testCase KdybyTests\Autowired\IntegrationTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Autowired
 */

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;
use Nette\DI;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/mocks/IntegrationPresenter.php';
require_once __DIR__ . '/mocks/LoremService.php';
require_once __DIR__ . '/mocks/DatagridComponent' . (class_exists('Nette\Application\UI\Component') ? '24' : '23') . '.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class IntegrationTest extends ContainerTestCase
{

	protected function setUp()
	{
		Tester\Environment::$checkAssertions = FALSE;
	}



	public function testFunctional()
	{
		$builder = new DI\ContainerBuilder;
		$builder->addDefinition('datagridFactory')
			->setImplement('KdybyTests\Autowired\IDatagridFactory');

		$builder->addDefinition('lorem')
			->setClass('KdybyTests\Autowired\LoremService');

		$builder->addDefinition('cacheStorage')
			->setClass('Nette\Caching\Storages\MemoryStorage');

		$container = $this->compileContainer($builder);

		$presenter = new IntegrationPresenter();
		$container->callMethod(array($presenter, 'injectProperties'));
		$container->callMethod(array($presenter, 'injectComponentFactories'));
	}

}

run(new IntegrationTest());
