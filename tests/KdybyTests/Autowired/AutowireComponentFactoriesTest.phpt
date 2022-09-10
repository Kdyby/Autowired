<?php
declare(strict_types=1);

namespace KdybyTests\Autowired;

use Kdyby;
use KdybyTests\Autowired\ComponentFactoriesFixtures\SillyComponent;
use KdybyTests\Autowired\ComponentFactoriesFixtures\SillyPresenter;
use KdybyTests\Autowired\ComponentFactoriesFixtures\WithMissingServicePresenter;
use KdybyTests\ContainerTestCase;
use KdybyTests\TestStorage;
use Nette;
use Tester\Assert;
use Tester\Expect;


require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowireComponentFactoriesTest extends ContainerTestCase
{

	private Nette\DI\Container $container;

	private TestStorage $cacheStorage;

	protected function setUp(): void
	{
		$this->container = $this->compileContainer('factories');
		$this->cacheStorage = $this->container->getByType(TestStorage::class);
	}

	public function testAutowireComponentFactories(): void
	{
		$presenter = new ComponentFactoriesFixtures\SillyPresenter();
		$this->container->callMethod([$presenter, 'injectComponentFactories']);

		Assert::type(SillyComponent::class, $presenter['autowired']);
		Assert::type(SillyComponent::class, $presenter['optional']);
		Assert::type(SillyComponent::class, $presenter['noTypehintName']);
		Assert::type(SillyComponent::class, $presenter['typehintedName']);

		Assert::equal(
			[Expect::match('~^Kdyby.Autowired.AutowireComponentFactories\\x00.*~')],
			array_keys($this->cacheStorage->getRecords()),
		);

		Assert::equal(
			[
				[
					'value' => TRUE,
					'dependencies' => $this->createExpectedDependencies(SillyPresenter::class, $this->container),
				],
			],
			array_values($this->cacheStorage->getRecords()),
		);
	}

	public function testAutowiringValidationIsNotRunWhenAlreadyCached(): void
	{
		$this->saveToCache(WithMissingServicePresenter::class, TRUE);

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
	 * @param string $component
	 * @param mixed $value
	 */
	private function saveToCache(string $component, $value): void
	{
		$key = [$component, (new \ReflectionClass($this->container))->getFileName()];
		$cache = new Nette\Caching\Cache($this->cacheStorage, 'Kdyby.Autowired.AutowireComponentFactories');
		$cache->save($key, $value);
	}

	/**
	 * @param class-string|object ...$classesOrObjects
	 * @return array<string, mixed>
	 */
	private function createExpectedDependencies(...$classesOrObjects): array
	{
		$callbacks = [];
		foreach ($classesOrObjects as $classesOrObject) {
			$callbacks[] = [
				[Nette\Caching\Cache::class, 'checkFile'],
				(new \ReflectionClass($classesOrObject))->getFileName(),
				Expect::type('int'),
			];
		}

		return ['callbacks' => $callbacks];
	}

}


(new AutowireComponentFactoriesTest())->run();
