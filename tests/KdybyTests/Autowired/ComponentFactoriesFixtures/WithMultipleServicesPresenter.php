<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

use Kdyby;
use Nette;


class WithMultipleServicesPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireComponentFactories;


	protected function createComponentSilly(ComponentFactoryWithMultipleServices $factory): SillyComponent
	{
		return new SillyComponent();
	}

}
