<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby\Autowired\Attributes\Autowire;


trait AutowireAttributeTrait
{

	#[Autowire]
	protected SampleService $serviceInTrait;

	#[Autowire(factory: SampleServiceFactory::class, arguments: ['attribute trait'])]
	public SampleService $factoryResultInTrait;

	public function getServiceInTrait(): SampleService
	{
		return $this->serviceInTrait;
	}

}
