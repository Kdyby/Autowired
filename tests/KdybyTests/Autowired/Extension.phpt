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



if (!class_exists('Tracy\Debugger')) {
	class_alias('Nette\Diagnostics\Debugger', 'Tracy\Debugger');
}

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

		try {
			Assert::match('%A%<div class="panel">%A?%<h2><a href="#netteBsPnl1" class="nette-toggle">Autowired</a></h2>%A%', file_get_contents($file));

		} catch (Tester\AssertException $e) {
			Assert::match('%A%<div class="panel">%A?%<h2><a href="#tracyBsPnl1" class="tracy-toggle">Autowired</a></h2>%A%', file_get_contents($file));
		}

	}

}

run(new ExtensionTest());
