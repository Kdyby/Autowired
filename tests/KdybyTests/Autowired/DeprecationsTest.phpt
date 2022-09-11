<?php
declare(strict_types=1);

namespace KdybyTests\Autowired;

use KdybyTests\Autowired\DeprecationsFixtures\SimplePresenter;
use KdybyTests\ContainerTestCase;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';



final class DeprecationsTest extends ContainerTestCase
{

	public function testDIExtensionNotRegistered(): void
	{
		$container = $this->compileContainer();
		$presenter = new SimplePresenter();

		Assert::error(
			function () use ($container, $presenter): void {
				$container->callMethod([$presenter, 'injectComponentFactories']);
			},
			E_USER_DEPRECATED,
			'Using Kdyby\Autowired\AutowireComponentFactories without registered AutowiredExtension is deprecated, register the extension in your config.',
		);

		Assert::error(
			function () use ($container, $presenter): void {
				$container->callMethod([$presenter, 'injectProperties']);
			},
			E_USER_DEPRECATED,
			'Using Kdyby\Autowired\AutowireProperties without registered AutowiredExtension is deprecated, register the extension in your config.',
		);
	}

}

(new DeprecationsTest())->run();
