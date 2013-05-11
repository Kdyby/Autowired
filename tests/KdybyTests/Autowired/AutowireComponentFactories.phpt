<?php

/**
 * Test: Kdyby\Autowired\AutowireComponentFactories.
 *
 * @testCase KdybyTests\Autowired\AutowireComponentFactoriesTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Autowired
 */

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;
use Nette\DI;
use Nette\PhpGenerator\PhpLiteral;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowireComponentFactoriesTest extends ContainerTestCase
{

	/**
	 * @var Nette\DI\Container
	 */
	private $container;



	protected function setUp()
	{
		$builder = new DI\ContainerBuilder;
		$builder->addDefinition('sampleFactory')
			->setImplement('KdybyTests\Autowired\ISillyComponentFactory');

		$builder->addDefinition('cacheStorage')
			->setClass('Nette\Caching\Storages\MemoryStorage');

		$this->container = $this->compileContainer($builder);
	}



	public function testFunctional()
	{
		$presenter = new SillyPresenter();
		$this->container->callMethod(array($presenter, 'injectComponentFactories'));

		Assert::true($presenter['silly'] instanceof SillyComponent);
		Assert::true($presenter['dummy'] instanceof SillyComponent);
	}

}



class SillyPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireComponentFactories;


	/**
	 * @param ISillyComponentFactory $factory
	 * @return SillyComponent
	 */
	protected function createComponentSilly(ISillyComponentFactory $factory)
	{
		return $factory->create();
	}



	/**
	 * @param string $name
	 * @param ISillyComponentFactory $factory
	 * @return SillyComponent
	 */
	protected function createComponentDummy($name, ISillyComponentFactory $factory)
	{
		return $factory->create();
	}

}


class SillyComponent extends Nette\Application\UI\PresenterComponent
{

	public function __construct()
	{
		parent::__construct();
	}

}



interface ISillyComponentFactory
{

	/** @return SillyComponent */
	function create();
}


run(new AutowireComponentFactoriesTest());
