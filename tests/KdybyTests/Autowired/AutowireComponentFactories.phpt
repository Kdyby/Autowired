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
use KdybyTests\ContainerTestCase;
use Nette;
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
		$this->container = $this->compileContainer('factories');
	}



	public function testFunctional()
	{
		$presenter = new SillyPresenter();
		$this->container->callMethod([$presenter, 'injectComponentFactories']);

		Assert::true($presenter['silly'] instanceof SillyComponent);
		Assert::true($presenter['dummy'] instanceof SillyComponent);
	}



	public function testMissingServiceException()
	{
		$container = $this->container;

		Assert::exception(function () use ($container) {
			$presenter = new WithMissingServicePresenter_wcf();
			$container->callMethod([$presenter, 'injectComponentFactories']);
		}, 'Kdyby\Autowired\MissingServiceException', 'No service of type SampleMissingService12345 found. Make sure the type hint in KdybyTests\Autowired\WithMissingServicePresenter_wcf::createComponentSilly() is written correctly and service of this type is registered.');
	}



	public function testTraitUserIsDescendantOfPresenterComponent()
	{
		$container = $this->container;

		Assert::exception(function () use ($container) {
			$component = new NonPresenterComponent_AcfProperties();
			$container->callMethod([$component, 'injectComponentFactories']);
		}, 'Kdyby\Autowired\MemberAccessException', 'Trait Kdyby\Autowired\AutowireComponentFactories can be used only in descendants of PresenterComponent.');
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



class WithMissingServicePresenter_wcf extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireComponentFactories;



	protected function createComponentSilly(\SampleMissingService12345 $factory)
	{

	}

}


class SillyComponent extends Nette\Application\UI\PresenterComponent
{

	public function __construct()
	{
	}

}



class NonPresenterComponent_AcfProperties
{
	use Kdyby\Autowired\AutowireComponentFactories;
}



interface ISillyComponentFactory
{

	/** @return SillyComponent */
	function create();
}


run(new AutowireComponentFactoriesTest());
