<?php

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;

class IntegrationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;

	/**
	 * @var LoremService
	 * @autowire
	 */
	public $service;

	/**
	 * @autowire
	 */
	public LoremService $typedService;


	protected function createComponentSilly(IDatagridFactory $factory)
	{
		return $factory->create();
	}

}
