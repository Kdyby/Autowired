<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Nette;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService as AliasedService;


class AutowireAnnotationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use AutowireAnnotationTrait;

	/**
	 * @autowire
	 */
	public SampleService $typedService;

	/**
	 * @var \KdybyTests\Autowired\PropertiesFixtures\SampleService
	 * @autowire
	 */
	public $fqnAnnotatedService;

	/**
	 * @var SampleService
	 * @autowire
	 */
	public $annotatedService;

	/**
	 * @var AliasedService
	 * @autowire
	 */
	public $aliasedAnnotatedService;

	/**
	 * @autowire("annotation", "fqn", factory=\KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory)
	 */
	public SampleService $fqnFactoryResult;

	/**
	 * @autowire("annotation", "unqualified", factory=SampleServiceFactory)
	 */
	public SampleService $factoryResult;

	/**
	 * @autowire("annotation", "aliased", factory=AliasedService)
	 */
	public SampleService $aliasedFactoryResult;

}
