<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Nette;


class WithMissingServiceFactoryPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;


	/**
	 * @autowire("string argument", "and another", factory=\KdybyTests\Autowired\PropertiesFixtures\MissingService)
	 */
	public SampleService $service;

}
