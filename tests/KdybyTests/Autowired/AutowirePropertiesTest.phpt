<?php
declare(strict_types=1);

namespace KdybyTests\Autowired;

use Kdyby;
use KdybyTests\Autowired\PropertiesFixtures\AutowireAnnotationPresenter;
use KdybyTests\Autowired\PropertiesFixtures\AutowireAttributePresenter;
use KdybyTests\Autowired\PropertiesFixtures\GenericFactory;
use KdybyTests\Autowired\PropertiesFixtures\SampleService;
use KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService;
use KdybyTests\Autowired\PropertiesFixtures\WithMissingServiceFactoryPresenter;
use KdybyTests\ContainerTestCase;
use Nette;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowirePropertiesTest extends ContainerTestCase
{

	private const AUTOWIRE_ANNOTATION_PRESENTER_CACHE = [
		'typedService' => [
			'type' => SampleService::class,
		],
		'fqnAnnotatedService' => [
			'type' => SampleService::class,
		],
		'annotatedService' => [
			'type' => SampleService::class,
		],
		'aliasedAnnotatedService' => [
			'type' => ImportedService::class,
		],
		'fqnFactoryResult' => [
			'factory' => SampleServiceFactory::class,
			'arguments' => ['annotation', 'fqn'],
			'type' => SampleService::class,
		],
		'factoryResult' => [
			'factory' => SampleServiceFactory::class,
			'arguments' => ['annotation', 'unqualified'],
			'type' => SampleService::class,
		],
		'aliasedFactoryResult' => [
			'factory' => ImportedService::class,
			'arguments' => ['annotation', 'aliased'],
			'type' => SampleService::class,
		],
		'genericFactoryResult' => [
			'factory' => GenericFactory::class,
			'arguments' => [ImportedService::class],
			'type' => ImportedService::class,
		],
		'typedServiceInTrait' => [
			'type' => SampleService::class,
		],
		'aliasedAnnotatedServiceInTrait' => [
			'type' => ImportedService::class,
		],
		'fqnFactoryResultInTrait' => [
			'factory' => SampleServiceFactory::class,
			'arguments' => ['annotation trait', 'fqn'],
			'type' => SampleService::class,
		],
		'aliasedFactoryResultInTrait' => [
			'factory' => ImportedService::class,
			'arguments' => ['annotation trait', 'aliased'],
			'type' => SampleService::class,
		],
	];

	private const AUTOWIRE_ATTRIBUTE_PRESENTER_CACHE = [
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

	private Nette\Caching\Cache $cache;

	protected function setUp(): void
	{
		$this->container = $this->compileContainer('properties');
		$this->cache = new Nette\Caching\Cache(
			$this->container->getService('cacheStorage'),
			'Kdyby.Autowired.AutowireProperties',
		);
	}

	public function testAutowireAnnotationProperties(): void
	{
		$presenter = new PropertiesFixtures\AutowireAnnotationPresenter();
		$this->container->callMethod([$presenter, 'injectProperties']);

		Assert::false(isset($presenter->typedService));
		Assert::type(SampleService::class, $presenter->typedService);

		Assert::false(isset($presenter->fqnAnnotatedService));
		Assert::type(SampleService::class, $presenter->fqnAnnotatedService);

		Assert::false(isset($presenter->annotatedService));
		Assert::type(SampleService::class, $presenter->annotatedService);

		Assert::false(isset($presenter->aliasedAnnotatedService));
		Assert::type(ImportedService::class, $presenter->aliasedAnnotatedService);

		Assert::false(isset($presenter->typedServiceInTrait));
		Assert::type(SampleService::class, $presenter->typedServiceInTrait);

		Assert::false(isset($presenter->aliasedAnnotatedServiceInTrait));
		Assert::type(ImportedService::class, $presenter->aliasedAnnotatedServiceInTrait);

		Assert::false(isset($presenter->fqnFactoryResult));
		Assert::type(SampleService::class, $presenter->fqnFactoryResult);
		Assert::same(['annotation', 'fqn'], $presenter->fqnFactoryResult->args);

		Assert::false(isset($presenter->factoryResult));
		Assert::type(SampleService::class, $presenter->factoryResult);
		Assert::same(['annotation', 'unqualified'], $presenter->factoryResult->args);

		Assert::false(isset($presenter->aliasedFactoryResult));
		Assert::type(SampleService::class, $presenter->aliasedFactoryResult);
		Assert::same(['annotation', 'aliased'], $presenter->aliasedFactoryResult->args);

		Assert::false(isset($presenter->fqnFactoryResultInTrait));
		Assert::type(SampleService::class, $presenter->fqnFactoryResultInTrait);
		Assert::same(['annotation trait', 'fqn'], $presenter->fqnFactoryResultInTrait->args);

		Assert::false(isset($presenter->aliasedFactoryResultInTrait));
		Assert::type(SampleService::class, $presenter->aliasedFactoryResultInTrait);
		Assert::same(['annotation trait', 'aliased'], $presenter->aliasedFactoryResultInTrait->args);

		Assert::false(isset($presenter->genericFactoryResult));
		Assert::type(ImportedService::class, $presenter->genericFactoryResult);

		Assert::same(
			self::AUTOWIRE_ANNOTATION_PRESENTER_CACHE,
			$this->cache->load($this->createCacheKey(AutowireAnnotationPresenter::class)),
		);
	}

	public function testAutowireAttributeProperties(): void
	{
		if (PHP_VERSION_ID < 8_00_00) {
			$this->skip('Attributes are supported on PHP >= 8.0');
		}

		$presenter = new PropertiesFixtures\AutowireAttributePresenter();
		$this->container->callMethod([$presenter, 'injectProperties']);

		Assert::false(isset($presenter->service));
		Assert::type(SampleService::class, $presenter->service);

		Assert::false(isset($presenter->serviceInTrait));
		Assert::type(SampleService::class, $presenter->serviceInTrait);

		Assert::false(isset($presenter->factoryResult));
		Assert::type(SampleService::class, $presenter->factoryResult);
		Assert::same(['attribute', NULL], $presenter->factoryResult->args);

		Assert::false(isset($presenter->factoryResultInTrait));
		Assert::type(SampleService::class, $presenter->factoryResultInTrait);
		Assert::same(['attribute trait', NULL], $presenter->factoryResultInTrait->args);

		Assert::false(isset($presenter->genericFactoryResult));
		Assert::type(ImportedService::class, $presenter->genericFactoryResult);

		Assert::same(
			self::AUTOWIRE_ATTRIBUTE_PRESENTER_CACHE,
			$this->cache->load($this->createCacheKey(AutowireAttributePresenter::class)),
		);
	}

	public function testUsingCachedMetadata(): void
	{
		$this->cache->save(
			$this->createCacheKey(AutowireAnnotationPresenter::class),
			self::AUTOWIRE_ANNOTATION_PRESENTER_CACHE,
		);

		$presenter = new PropertiesFixtures\AutowireAnnotationPresenter();
		$this->container->callMethod([$presenter, 'injectProperties']);

		Assert::false(isset($presenter->typedService));
		Assert::type(SampleService::class, $presenter->typedService);

		Assert::false(isset($presenter->fqnFactoryResult));
		Assert::type(SampleService::class, $presenter->fqnFactoryResult);
		Assert::same(['annotation', 'fqn'], $presenter->fqnFactoryResult->args);
	}

	public function testAutowiringValidationIsNotRunWhenAlreadyCached(): void
	{
		$this->cache->save($this->createCacheKey(WithMissingServiceFactoryPresenter::class), []);

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

	public function testInvalidServiceFactoryTypeException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\WithInvalidFactoryTypePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingClassException::class,
			'Neither class "string" or "KdybyTests\Autowired\PropertiesFixtures\string" was found, please check the typehint on KdybyTests\Autowired\PropertiesFixtures\WithInvalidFactoryTypePresenter::$service in annotation @autowire.',
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
			'Missing property typehint or annotation @var on KdybyTests\Autowired\PropertiesFixtures\WithMissingPropertyTypePresenter::$service.',
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
			'Autowired properties must be protected or public. Please fix visibility of KdybyTests\Autowired\PropertiesFixtures\PrivateAutowiredPropertyPresenter::$service or remove the @autowire annotation.',
		);
	}

	public function testAutowireAnnotationWrongCaseException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\AutowireAnnotationWrongCasePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\UnexpectedValueException::class,
			'Annotation @Autowire on KdybyTests\Autowired\PropertiesFixtures\AutowireAnnotationWrongCasePresenter::$service should be fixed to lowercase @autowire.',
		);
	}

	public function testAutowireAnnotationTypoException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container): void {
				$presenter = new PropertiesFixtures\AutowireAnnotationTypoPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\UnexpectedValueException::class,
			'Annotation @autowired on KdybyTests\Autowired\PropertiesFixtures\AutowireAnnotationTypoPresenter::$service should be fixed to lowercase @autowire.',
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
	 * @return array<mixed>
	 */
	private function createCacheKey(string $component): array
	{
		return [$component, (new \ReflectionClass($this->container))->getFileName()];
	}

}


(new AutowirePropertiesTest())->run();
