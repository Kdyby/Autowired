<?php

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;

class Php74PropertyTypesPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;

	/**
	 * @autowire
	 */
	public SampleService $service;

}
