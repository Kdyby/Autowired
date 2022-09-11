<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\IntegrationFixtures;

use Kdyby;
use Kdyby\Autowired\Attributes\Autowire;
use Nette;

class IntegrationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;

	#[Autowire]
	public LoremService $service;

	#[Autowire(factory: DatagridFactory::class)]
	public DatagridComponent $factoryResult;

	protected function createComponentSilly(DatagridFactory $factory): DatagridComponent
	{
		return $factory->create();
	}

}
