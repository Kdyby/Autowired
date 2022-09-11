<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService as AliasedServiceInTrait;


trait AutowireAnnotationTrait
{

	/**
	 * @autowire
	 */
	public SampleService $typedServiceInTrait;

	/**
	 * @autowire("annotation trait", "fqn", factory=\KdybyTests\Autowired\PropertiesFixtures\SampleServiceFactory)
	 */
	public SampleService $fqnFactoryResultInTrait;

	/**
	 * @autowire("annotation trait", "aliased", factory=AliasedServiceInTrait)
	 */
	public SampleService $aliasedFactoryResultInTrait;

	protected AliasedServiceInTrait $aliasedService;

}
