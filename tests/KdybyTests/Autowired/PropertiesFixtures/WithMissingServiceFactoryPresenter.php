<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Kdyby\Autowired\Attributes\Autowire;
use Nette;


class WithMissingServiceFactoryPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;

	#[Autowire(factory: MissingService::class, arguments: ['string argument', 'and another'])]
	public SampleService $service;

}
