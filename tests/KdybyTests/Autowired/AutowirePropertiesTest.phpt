<?php

/**
 * Test: Kdyby\Autowired\AutowireProperties.
 *
 * @testCase KdybyTests\Autowired\AutowirePropertiesTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Autowired
 */

namespace KdybyTests\Autowired;

use Kdyby;
use KdybyTests\Autowired\PropertiesFixtures\AutowireAnnotationPresenter;
use KdybyTests\Autowired\PropertiesFixtures\SampleService;
use KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService;
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
			'value' => null,
			'type' => SampleService::class,
		],
		'fqnAnnotatedService' => [
			'value' => null,
			'type' => SampleService::class,
		],
		'annotatedService' => [
			'value' => null,
			'type' => SampleService::class,
		],
		'aliasedAnnotatedService' => [
			'value' => null,
			'type' => ImportedService::class,
		],
		'fqnFactoryResult' => [
			'value' => null,
			'type' => SampleService::class,
			'arguments' => ['annotation', 'fqn'],
			'factory' => SampleServiceFactory::class,
		],
		'factoryResult' => [
			'value' => null,
			'type' => SampleService::class,
			'arguments' => ['annotation', 'unqualified'],
			'factory' => SampleServiceFactory::class,
		],
		'aliasedFactoryResult' => [
			'value' => null,
			'type' => SampleService::class,
			'arguments' => ['annotation', 'aliased'],
			'factory' => ImportedService::class,
		],
		'typedServiceInTrait' => [
			'value' => null,
			'type' => SampleService::class,
		],
		'aliasedAnnotatedServiceInTrait' => [
			'value' => null,
			'type' => ImportedService::class,
		],
		'fqnFactoryResultInTrait' => [
			'value' => null,
			'type' => SampleService::class,
			'arguments' => ['annotation trait', 'fqn'],
			'factory' => SampleServiceFactory::class,
		],
		'aliasedFactoryResultInTrait' => [
			'value' => null,
			'type' => SampleService::class,
			'arguments' => ['annotation trait', 'aliased'],
			'factory' => ImportedService::class,
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

		Assert::same(
			self::AUTOWIRE_ANNOTATION_PRESENTER_CACHE,
			$this->cache->load($this->createCacheKey(AutowireAnnotationPresenter::class)),
		);
	}


	public function testServiceFactoryReturnTypeMismatchException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithServiceFactoryReturnTypeMismatchPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\UnexpectedValueException::class,
			'The property KdybyTests\Autowired\PropertiesFixtures\WithServiceFactoryReturnTypeMismatchPresenter::$service requires KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService, but factory of type KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory, that creates KdybyTests\Autowired\PropertiesFixtures\SampleService was provided.'
		);
	}

	public function testMissingServiceFactoryException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithMissingServiceFactoryPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service factory for KdybyTests\Autowired\PropertiesFixtures\WithMissingServiceFactoryPresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\MissingService not found. Did you add it to configuration file?'
		);
	}

	public function testMultipleServiceFactoriesException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithMultipleServiceFactoriesPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service factory for KdybyTests\Autowired\PropertiesFixtures\WithMultipleServiceFactoriesPresenter::$service: Multiple services of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithMultipleServices found: one, two.'
		);
	}

	public function testDisabledAutowiringServiceFactoryException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithDisabledAutowiringServiceFactoryPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service factory for KdybyTests\Autowired\PropertiesFixtures\WithDisabledAutowiringServiceFactoryPresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithDisabledAutowiring is not autowired or is missing in di › export › types.'
		);
	}

	public function testInvalidServiceFactoryTypeException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithInvalidFactoryTypePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingClassException::class,
			'Neither class "string" or "KdybyTests\Autowired\PropertiesFixtures\string" was found, please check the typehint on KdybyTests\Autowired\PropertiesFixtures\WithInvalidFactoryTypePresenter::$service in annotation @autowire.'
		);
	}

	public function testDisabledAutowiringServiceException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithDisabledAutowiringServicePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service for KdybyTests\Autowired\PropertiesFixtures\WithDisabledAutowiringServicePresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithDisabledAutowiring is not autowired or is missing in di › export › types.'
		);
	}

	public function testMultipleServicesException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithMultipleServicesPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service for KdybyTests\Autowired\PropertiesFixtures\WithMultipleServicesPresenter::$service: Multiple services of type KdybyTests\Autowired\PropertiesFixtures\FactoryWithMultipleServices found: one, two.'
		);
	}

	public function testMissingServiceException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithMissingServicePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingServiceException::class,
			'Unable to autowire service for KdybyTests\Autowired\PropertiesFixtures\WithMissingServicePresenter::$service: Service of type KdybyTests\Autowired\PropertiesFixtures\MissingService not found. Did you add it to configuration file?'
		);
	}

	public function testInvalidPropertyTypeException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithInvalidPropertyTypePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MissingClassException::class,
			'Class "string" not found, please check the typehint on KdybyTests\Autowired\PropertiesFixtures\WithInvalidPropertyTypePresenter::$service.'
		);
	}

	public function testMissingPropertyTypeException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\WithMissingPropertyTypePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\InvalidStateException::class,
			'Missing property typehint or annotation @var on KdybyTests\Autowired\PropertiesFixtures\WithMissingPropertyTypePresenter::$service.'
		);
	}

	public function testPrivateAutowiredPropertyException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\PrivateAutowiredPropertyPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\MemberAccessException::class,
			'Autowired properties must be protected or public. Please fix visibility of KdybyTests\Autowired\PropertiesFixtures\PrivateAutowiredPropertyPresenter::$service or remove the @autowire annotation.'
		);
	}



	public function testAutowireAnnotationWrongCaseException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\AutowireAnnotationWrongCasePresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\UnexpectedValueException::class,
			'Annotation @Autowire on KdybyTests\Autowired\PropertiesFixtures\AutowireAnnotationWrongCasePresenter::$service should be fixed to lowercase @autowire.'
		);
	}



	public function testAutowireAnnotationTypoException(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$presenter = new PropertiesFixtures\AutowireAnnotationTypoPresenter();
				$container->callMethod([$presenter, 'injectProperties']);
			},
			Kdyby\Autowired\UnexpectedValueException::class,
			'Annotation @autowired on KdybyTests\Autowired\PropertiesFixtures\AutowireAnnotationTypoPresenter::$service should be fixed to lowercase @autowire.'
		);
	}


	public function testTraitUserIsDescendantOfPresenterComponent(): void
	{
		$container = $this->container;

		Assert::exception(
			function () use ($container) {
				$component = new PropertiesFixtures\NonPresenterComponent();
				$container->callMethod([$component, 'injectProperties']);
			},
			Kdyby\Autowired\MemberAccessException::class,
			'Trait Kdyby\Autowired\AutowireProperties can be used only in descendants of PresenterComponent.'
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
