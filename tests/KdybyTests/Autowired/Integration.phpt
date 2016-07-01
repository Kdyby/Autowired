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



class IntegrationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;

	/**
	 * @var LoremService
	 * @autowire
	 */
	public $service;



	protected function createComponentSilly(IDatagridFactory $factory)
	{
		return $factory->create();
	}

}



class DatagridComponent extends Nette\Application\UI\Component
{

	public function __construct()
	{
		parent::__construct();
	}

}



interface IDatagridFactory
{

	/** @return DatagridComponent */
	function create();
}



class LoremService
{

}

run(new IntegrationTest());
