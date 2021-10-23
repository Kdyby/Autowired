<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

use Kdyby;
use Nette;


class WithMissingServicePresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireComponentFactories;


	protected function createComponentSilly(ComponentFactoryWithMissingService $factory): SillyComponent
	{
		return new SillyComponent();
	}

}
