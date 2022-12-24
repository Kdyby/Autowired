<?php
declare(strict_types=1);

namespace KdybyTests\Autowired;

use Kdyby;
use Kdyby\Autowired\Caching\CacheFactory;
use KdybyTests\Autowired\PropertiesFixtures\AutowireAttributeControl;
use KdybyTests\Autowired\PropertiesFixtures\AutowireAttributeTrait;
use KdybyTests\Autowired\PropertiesFixtures\BaseControl;
use KdybyTests\Autowired\PropertiesFixtures\GenericFactory;
use KdybyTests\Autowired\PropertiesFixtures\SampleService;
use KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService;
use KdybyTests\Autowired\PropertiesFixtures\WithMissingServiceFactoryPresenter;
use KdybyTests\ContainerTestCase;
use KdybyTests\TestStorage;
use Nette;
use Tester\Assert;
use Tester\Expect;


require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowirePropertiesTest extends ContainerTestCase
{

	private const AUTOWIRE_ATTRIBUTE_CONTROL_CACHE = [
		'baseService' => [
			'type' => SampleService::class,
		],
		'service' => [
			'type' => SampleService::class,
		],
		'factoryResult' => [
			'factory' => SampleServiceFactory::class,
			'arguments' => ['attribute'],
			'type' => SampleService::class,
		],
		'genericFactoryResult' => [
			'factory' => GenericFactory::class,
			'arguments' => [ImportedService::class],
			'type' => ImportedService::class,
		],
		'aliasedService' => [
			'type' => ImportedService::class,
		],
		'serviceInTrait' => [
			'type' => SampleService::class,
		],
		'factoryResultInTrait' => [
			'factory' => SampleServiceFactory::class,
			'arguments' => ['attribute trait'],
			'type' => SampleService::class,
		],
	];

	private Nette\DI\Container $container;

	private TestStorage $cacheStorage;

	protected function setUp(): void
	{
		$this->container = $this->compileContainer('properties');
		$this->cacheStorage = $this->container->getByType(TestStorage::class);
	}

	public function testAutowireAttributeProperties(): void
	{
		$control = new PropertiesFixtures\AutowireAttributeControl();
		$this->container->callMethod([$control, 'injectProperties']);

		Assert::false(isset($control->baseService));
		Assert::type(SampleService::class, $control->baseService);

		Assert::false(isset($control->service));
		Assert::type(SampleService::class, $control->service);

		Assert::false(isset($control->serviceInTrait));
		Assert::type(SampleService::class, $control->getServiceInTrait());

		Assert::false(isset($control->factoryResult));
		Assert::type(SampleService::class, $control->factoryResult);
		Assert::same(['attribute', NULL], $control->factoryResult->args);

		Assert::false(isset($control->factoryResultInTrait));
		Assert::type(SampleService::class, $control->factoryResultInTrait);
		Assert::same(['attribute trait', NULL], $control->factoryResultInTrait->args);

		Assert::false(isset($control->genericFactoryResult));
		Assert::type(ImportedService::class, $control->genericFactoryResult);

		Assert::equal(
			[Expect::match('~^Kdyby.Autowired.AutowireProperties\\x00.*~')],
			array_keys($this->cacheStorage->getRecords()),
		);

		Assert::equal(
			[
				[
					'value' => self::AUTOWIRE_ATTRIBUTE_CONTROL_CACHE,
					'dependencies' => $this->createExpectedDependencies(
						BaseControl::class,
						AutowireAttributeControl::class,
						AutowireAttributeTrait::class,
						$this->container,
					),
				],
			],
			array_values($this->cacheStorage->getRecords()),
		);
	}

	public function testUsingCachedMetadata(): void
	{
		$this->saveToCache(
			AutowireAttributeControl::class,
			self::AUTOWIRE_ATTRIBUTE_CONTROL_CACHE,
		);

		$control = new PropertiesFixtures\AutowireAttributeControl();
		$this->container->callMethod([$control, 'injectProperties']);

		Assert::false(isset($control->service));
		Assert::type(SampleService::class, $control->service);

		Assert::false(isset($control->factoryResult));
		Assert::type(SampleService::class, $control->factoryResult);
		Assert::same(['attribute', NULL], $control->factoryResult->args);
	}

	public function testAutowiringValidationIsNotRunWhenAlreadyCached(): void
	{
		$this->saveToCache(WithMissingServiceFactoryPresenter::class, []);

		Assert::noError(
			function (): void {
				$presenter = new PropertiesFixtures\WithMissingServiceFactoryPresenter();
				$this->container->callMethod([$presenter, 'injectProperties']);
			},
		);
	}

	public function testServiceFactoryReturnTypeMismatchException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithServiceFactoryReturnTypeMismatchPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\UnexpectedValueException::class,
			'The property KdybyTests\Autowired\PropertiesFixtures\WithServiceFactoryReturnTypeMismatchPresenter::$service requires KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService, but factory of type KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory, that creates KdybyTests\Autowired\PropertiesFixtures\SampleService was provided.',
		);
	}

	public function testMissingServiceFactoryException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithMissingServiceFactoryPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service factory for KdybyTests\Autowired\PropertiesFixtures\WithMissingServiceFactoryPresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\MissingService not found. Did you add it to configuration file?',
		);
	}

	public function testMultipleServiceFactoriesException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithMultipleServiceFactoriesPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service factory for KdybyTests\Autowired\PropertiesFixtures\WithMultipleServiceFactoriesPresenter::$service: Multiple services of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithMultipleServices found: one, two.',
		);
	}

	public function testDisabledAutowiringServiceFactoryException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithDisabledAutowiringServiceFactoryPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service factory for KdybyTests\Autowired\PropertiesFixtures\WithDisabledAutowiringServiceFactoryPresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithDisabledAutowiring is not autowired or is missing in di › export › types.',
		);
	}

	public function testDisabledAutowiringServiceException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithDisabledAutowiringServicePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service for KdybyTests\Autowired\PropertiesFixtures\WithDisabledAutowiringServicePresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithDisabledAutowiring is not autowired or is missing in di › export › types.',
		);
	}

	public function testMultipleServicesException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithMultipleServicesPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service for KdybyTests\Autowired\PropertiesFixtures\WithMultipleServicesPresenter::$service: Multiple services of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithMultipleServices found: one, two.',
		);
	}

	public function testMissingServiceException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithMissingServicePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service for KdybyTests\Autowired\PropertiesFixtures\WithMissingServicePresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\MissingService not found. Did you add it to configuration file?',
		);
	}

	public function testInvalidPropertyTypeException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithInvalidPropertyTypePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingClassException::class,
			'Class "string" not found, please check the typehint on KdybyTests\Autowired\PropertiesFixtures\WithInvalidPropertyTypePresenter::$service.',
		);
	}

	public function testMissingPropertyTypeException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithMissingPropertyTypePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\InvalidStateException::class,
			'Missing property typehint on KdybyTests\Autowired\PropertiesFixtures\WithMissingPropertyTypePresenter::$service.',
		);
	}

	public function testPrivateAutowiredPropertyException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\PrivateAutowiredPropertyPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MemberAccessException::class,
			'Autowired properties must be protected or public. Please fix visibility of KdybyTests\Autowired\PropertiesFixtures\PrivateAutowiredPropertyPresenter::$service or remove the Autowire attribute.',
		);
	}

	public function testTraitUserIsDescendantOfPresenterComponent(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$component = new PropertiesFixtures\NonPresenterComponent();
				$container->callMethod([$component, 'injectProperties']);
			},
			Kdyby\Autowired\MemberAccessException::class,
			'Trait Kdyby\Autowired\AutowireProperties can be used only in descendants of Nette\Application\UI\Component.',
		);
	}

	/**
	 * @param class-string<Nette\Application\UI\Component> $component
	 * @param mixed $value
	 * @return void
	 */
	private function saveToCache(string $component, mixed $value): void
	{
		CacheFactory::fromContainer($this->container)
			->create($component, 'Kdyby.Autowired.AutowireProperties')
			->save($value);
	}

	/**
	 * @param class-string|object ...$classesOrObjects
	 * @return array<string, mixed>
	 */
	private function createExpectedDependencies(string|object ...$classesOrObjects): array
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


(new AutowirePropertiesTest())->run();
