<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

use Kdyby;
use Nette;


class WithDisabledAutowiringPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireComponentFactories;


	protected function createComponentSilly(ComponentFactoryWithDisabledAutowiring $factory): SillyComponent
	{
		return new SillyComponent();
	}

}
