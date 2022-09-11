<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Kdyby\Autowired\Attributes\Autowire;
use Nette;


class WithMultipleServiceFactoriesPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;

	#[Autowire(factory: FactoryWithMultipleServices::class)]
	public SampleService $service;

}
