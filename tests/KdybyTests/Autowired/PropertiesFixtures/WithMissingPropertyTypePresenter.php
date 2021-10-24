<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Nette;


class WithMissingPropertyTypePresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;


	/**
	 * @autowire
	 */
	public $service;

}
