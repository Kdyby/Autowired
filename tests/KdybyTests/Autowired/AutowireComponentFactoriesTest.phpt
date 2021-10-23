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
use KdybyTests\Autowired\ComponentFactoriesFixtures\SillyComponent;
use KdybyTests\ContainerTestCase;
use Nette;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowireComponentFactoriesTest extends ContainerTestCase
{

	private Nette\DI\Container $container;



	protected function setUp(): void
	{
		$this->container = $this->compileContainer('factories');
	}



	public function testAutowireComponentFactories(): void
	{
		$presenter = new ComponentFactoriesFixtures\SillyPresenter();
		$this->container->callMethod([$presenter, 'injectComponentFactories']);

		Assert::type(SillyComponent::class, $presenter['autowired']);
		Assert::type(SillyComponent::class, $presenter['optional']);
		Assert::type(SillyComponent::class, $presenter['noTypehintName']);
		Assert::type(SillyComponent::class, $presenter['typehintedName']);
	}



	public function testMissingServiceException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new ComponentFactoriesFixtures\WithMissingServicePresenter();
				$container->callMethod([$presenter, 'injectComponentFactories']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'No service of type KdybyTests\Autowired\ComponentFactoriesFixtures\ComponentFactoryWithMissingService found. Make sure the type hint in KdybyTests\Autowired\ComponentFactoriesFixtures\WithMissingServicePresenter::createComponentSilly%S?% is written correctly and service of this type is registered.',
		);
	}

	public function testTraitUserIsDescendantOfPresenterComponent(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$component = new ComponentFactoriesFixtures\NonPresenterComponent();
				$container->callMethod([$component, 'injectComponentFactories']);
			},
			Kdyby\Autowired\MemberAccessException::class,
			'Trait Kdyby\Autowired\AutowireComponentFactories can be used only in descendants of PresenterComponent.',
		);
	}

}


(new AutowireComponentFactoriesTest())->run();
