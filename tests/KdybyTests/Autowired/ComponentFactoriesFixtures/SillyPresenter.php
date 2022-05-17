<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

use Kdyby;
use Nette;


class SillyPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireComponentFactories;

	protected function createComponentAutowired(ComponentFactory $factory): SillyComponent
	{
		return $factory->create();
	}

	protected function createComponentOptional(?ComponentFactoryWithMissingService $factory): SillyComponent
	{
		return new SillyComponent();
	}

	protected function createComponentNoTypehintName(string|int $name, ComponentFactory $factory): SillyComponent
	{
		return $factory->create();
	}

	protected function createComponentTypehintedName(string $name, ComponentFactory $factory): SillyComponent
	{
		return $factory->create();
	}

	public function ignoredMethod(ComponentFactoryWithMissingService $factory): void
	{
	}

}
