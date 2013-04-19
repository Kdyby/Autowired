<?php

/**
 * Test: Kdyby\Autowired\AutowireProperties.
 *
 * @testCase KdybyTests\Autowired\AutowirePropertiesTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Autowired
 */

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;
use Nette\DI;
use Nette\PhpGenerator\PhpLiteral;
use Tester;
use Tester\Assert;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowirePropertiesTest extends Tester\TestCase
{

	/**
	 * @var Nette\DI\Container
	 */
	private $container;



	protected function setUp()
	{
		$builder = new DI\ContainerBuilder;
		$builder->addDefinition('sampleFactory')
			->setFactory('KdybyTests\Autowired\SampleService', array(new PhpLiteral('$name'), new PhpLiteral('$secondName')))
			->setImplement('KdybyTests\Autowired\ISampleServiceFactory')
			->setParameters(array('name', 'secondName' => NULL))
			->setShared(TRUE)->setAutowired(TRUE);

		$builder->addDefinition('sample')
			->setClass('KdybyTests\Autowired\SampleService', array('shared'));

		$builder->addDefinition('cacheStorage')
			->setClass('Nette\Caching\Storages\MemoryStorage');

		// run-time
		$code = implode('', $builder->generateClasses());
		file_put_contents(TEMP_DIR . '/code.php', "<?php\n$code");
		require TEMP_DIR . '/code.php';

		$this->container = new \Container;
	}



	public function testFunctional()
	{
		$presenter = new DummyPresenter();
		Assert::null($presenter->service);
		Assert::null($presenter->factoryResult);
		Assert::null($presenter->secondFactoryResult);

		$this->container->callMethod(array($presenter, 'injectProperties'));

		Assert::true($presenter->service instanceof SampleService);
		Assert::same(array('shared'), $presenter->service->args);

		Assert::true($presenter->factoryResult instanceof SampleService);
		Assert::same(array('string argument', NULL), $presenter->factoryResult->args);

		Assert::true($presenter->secondFactoryResult instanceof SampleService);
		Assert::same(array('string argument', 'and another'), $presenter->secondFactoryResult->args);
	}

}



class DummyPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;

	/**
	 * @var SampleService
	 * @autowire
	 */
	public $service;

	/**
	 * @var SampleService
	 * @autowire("string argument", factory=\KdybyTests\Autowired\ISampleServiceFactory)
	 */
	public $factoryResult;

	/**
	 * @var SampleService
	 * @autowire("string argument", "and another", factory=\KdybyTests\Autowired\ISampleServiceFactory)
	 */
	public $secondFactoryResult;

}



class SampleService
{
	public $args;

	public function __construct($name, $secondName = NULL)
	{
		$this->args = func_get_args();
	}
}



interface ISampleServiceFactory
{
	/** @return SampleService */
	function create($name, $secondName = NULL);
}


\run(new AutowirePropertiesTest());
