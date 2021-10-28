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
use KdybyTests\Autowired\ComponentFactoriesFixtures\WithMissingServicePresenter;
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

	private Nette\Caching\Cache $cache;



	protected function setUp(): void
	{
		$this->container = $this->compileContainer('factories');
		$this->cache = new Nette\Caching\Cache(
			$this->container->getService('cacheStorage'),
			'Kdyby.Autowired.AutowireComponentFactories',
		);
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


	public function testAutowiringValidationIsNotRunWhenAlreadyCached(): void
	{
		$this->cache->save($this->createCacheKey(WithMissingServicePresenter::class), TRUE);

		Assert::noError(
			function (): void {
				$presenter = new ComponentFactoriesFixtures\WithMissingServicePresenter();
				$this->container->callMethod([$presenter, 'injectComponentFactories']);
			},
		);
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
			'Service of type KdybyTests\Autowired\ComponentFactoriesFixtures\ComponentFactoryWithMissingService required by $factory in KdybyTests\Autowired\ComponentFactoriesFixtures\WithMissingServicePresenter::createComponentSilly() not found. Did you add it to configuration file?',
		);
	}

	public function testMultipleServicesException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new ComponentFactoriesFixtures\WithMultipleServicesPresenter();
				$container->callMethod([$presenter, 'injectComponentFactories']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Service of type KdybyTests\Autowired\ComponentFactoriesFixtures\ComponentFactoryWithMultipleServices required by $factory in KdybyTests\Autowired\ComponentFactoriesFixtures\WithMultipleServicesPresenter::createComponentSilly() not found. Did you add it to configuration file?',
		);
	}

	public function testDisabledAutowiringException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new ComponentFactoriesFixtures\WithDisabledAutowiringPresenter();
				$container->callMethod([$presenter, 'injectComponentFactories']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Service of type KdybyTests\Autowired\ComponentFactoriesFixtures\ComponentFactoryWithDisabledAutowiring required by $factory in KdybyTests\Autowired\ComponentFactoriesFixtures\WithDisabledAutowiringPresenter::createComponentSilly() not found. Did you add it to configuration file?',
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
			'Trait Kdyby\Autowired\AutowireComponentFactories can be used only in descendants of Nette\Application\UI\Component.',
		);
	}

	/**
	 * @return array<mixed>
	 */
	private function createCacheKey(string $component): array
	{
		return [$component, (new \ReflectionClass($this->container))->getFileName()];
	}

}


(new AutowireComponentFactoriesTest())->run();
