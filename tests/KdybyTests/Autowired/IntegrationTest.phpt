<?php

/**
 * Test: Kdyby\Autowired\Integration.
 *
 * @testCase KdybyTests\Autowired\IntegrationTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Autowired
 */

namespace KdybyTests\Autowired;

use Kdyby;
use KdybyTests\Autowired\IntegrationFixtures\IntegrationPresenter;
use KdybyTests\ContainerTestCase;
use Tester;
use Tester\Assert;


require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class IntegrationTest extends ContainerTestCase
{

	public function testFunctional(): void
	{
		$container = $this->compileContainer('integration');

		Assert::noError(function () use ($container): void {
			$presenter = new IntegrationPresenter();
			$container->callMethod([$presenter, 'injectProperties']);
			$container->callMethod([$presenter, 'injectComponentFactories']);
		});
	}

}

(new IntegrationTest())->run();
