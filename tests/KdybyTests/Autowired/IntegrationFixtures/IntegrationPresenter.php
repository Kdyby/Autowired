<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\IntegrationFixtures;

use Kdyby;
use Nette;

class IntegrationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;

	/**
	 * @autowire
	 */
	public LoremService $service;

	/**
	 * @autowire(factory=\KdybyTests\Autowired\IntegrationFixtures\DatagridFactory)
	 */
	public DatagridComponent $factoryResult;

	protected function createComponentSilly(DatagridFactory $factory): DatagridComponent
	{
		return $factory->create();
	}

}
