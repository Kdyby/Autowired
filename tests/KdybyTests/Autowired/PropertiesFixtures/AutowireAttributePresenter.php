<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Kdyby\Autowired\Attributes\Autowire;
use KdybyTests\Autowired\PropertiesFixtures\UseExpansion\ImportedService;
use Nette;


class AutowireAttributePresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use AutowireAttributeTrait;

	#[Autowire]
	public SampleService $service;

	#[Autowire(SampleServiceFactory::class, ['attribute'])]
	public SampleService $factoryResult;

	#[Autowire(GenericFactory::class, [ImportedService::class])]
	public ImportedService $genericFactoryResult;

}
