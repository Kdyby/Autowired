<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Nette;


class WithInvalidFactoryTypePresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;


	/**
	 * @autowire("string argument", "and another", factory=string)
	 */
	public SampleService $service;

}
