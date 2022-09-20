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

	protected function createComponentOptional(?ComponentFactoryWithMissingService $factory = NULL): SillyComponent
	{
		return new SillyComponent();
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
	 */
	protected function createComponentNoTypehintName($name, ComponentFactory $factory): SillyComponent
	{
		return $factory->create();
	}

	protected function createComponentTypehintedName(string $name, ?ComponentFactory $factory = NULL): SillyComponent
	{
		assert($factory !== NULL);
		return $factory->create();
	}

	public function ignoredMethod(ComponentFactoryWithMissingService $factory): void
	{
	}

}
