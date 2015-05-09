<?php

/**
 * Test: Kdyby\Autowired\Extension.
 *
 * @testCase Kdyby\Autowired\ExtensionTest
 * @author Filip Procházka <filip@prochazka.su>
 * @package Kdyby\Autowired
 */

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;
use Tester;
use Tester\Assert;
use Tracy\Debugger;

require_once __DIR__ . '/../bootstrap.php';



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class ExtensionTest extends Tester\TestCase
{

	public function testFunctionality()
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		Kdyby\Autowired\DI\AutowiredExtension::register($config);
		$config->createContainer(); // init panel

		Debugger::$logDirectory = TEMP_DIR;
		$refl = new Nette\Reflection\Property('\Nette\Application\UI\Presenter', 'onShutdown');
		$file = Debugger::log(new Kdyby\Autowired\MissingServiceException("Missing service blabla", $refl));

		Assert::match('%A%<div%a% class="panel">%A?%<h2><a%a% class="tracy-toggle">Autowired</a></h2>%A%', file_get_contents($file));
	}

}

run(new ExtensionTest());
