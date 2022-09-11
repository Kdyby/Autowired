<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService as AliasedService;
use Nette;


class AutowireAnnotationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use AutowireAnnotationTrait;

	/**
	 * @autowire
	 */
	public SampleService $typedService;

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

	/**
	 * @autowire("KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService", factory=GenericFactory)
	 */
	public ImportedService $genericFactoryResult;

	protected AliasedService $aliasedService;

}
