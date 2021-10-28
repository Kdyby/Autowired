<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby\Autowired\Attributes\Autowire;


trait AutowireAttributeTrait
{

	#[Autowire]
	public SampleService $serviceInTrait;

	#[Autowire(factory: SampleServiceFactory::class, arguments: ['attribute trait'])]
	public SampleService $factoryResultInTrait;

}
