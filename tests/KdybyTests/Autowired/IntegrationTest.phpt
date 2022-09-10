<?php
declare(strict_types=1);

namespace KdybyTests\Autowired;

use KdybyTests\Autowired\IntegrationFixtures\DatagridComponent;
use KdybyTests\Autowired\IntegrationFixtures\IntegrationPresenter;
use KdybyTests\Autowired\IntegrationFixtures\LoremService;
use KdybyTests\ContainerTestCase;
use KdybyTests\TestStorage;
use Tester\Assert;
use Tester\Expect;


require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class IntegrationTest extends ContainerTestCase
{

	public function testFunctional(): void
	{
		$container = $this->compileContainer('integration');

		/** @var IntegrationPresenter $presenter */
		$presenter = $container->getByName('presenter');
		Assert::type(IntegrationPresenter::class, $presenter);

		Assert::type(LoremService::class, $presenter->service);
		Assert::type(DatagridComponent::class, $presenter->factoryResult);
		Assert::type(DatagridComponent::class, $presenter->getComponent('silly'));

		/** @var TestStorage $cacheStorage */
		$cacheStorage = $container->getByName('autowired.cacheStorage');
		Assert::type(TestStorage::class, $cacheStorage);

		Assert::equal(
			[
				Expect::match('~^Kdyby.Autowired.AutowireProperties\\x00.*~'),
				Expect::match('~^Kdyby.Autowired.AutowireComponentFactories\\x00.*~'),
			],
			array_keys($cacheStorage->getRecords()),
		);
	}

}

(new IntegrationTest())->run();
