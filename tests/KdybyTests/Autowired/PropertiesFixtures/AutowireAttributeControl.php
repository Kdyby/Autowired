<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby\Autowired\Attributes\Autowire;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService as AliasedService;

class AutowireAttributeControl extends BaseControl
{

	use AutowireAttributeTrait;

	#[Autowire]
	public SampleService $service;

	#[Autowire(SampleServiceFactory::class, ['attribute'])]
	public SampleService $factoryResult;

	#[Autowire(GenericFactory::class, [ImportedService::class])]
	public ImportedService $genericFactoryResult;

	#[Autowire]
	public AliasedService $aliasedService;

}
