<?php
declare(strict_types=1);

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

	public function testFunctionality(): void
	{
		$config = new Nette\Configurator();
		$config->setTempDirectory(TEMP_DIR);
		$config->addConfig(__DIR__ . '/../config/application.neon');
		Kdyby\Autowired\DI\AutowiredExtension::register($config);
		$config->createContainer(); // init panel

		Debugger::$logDirectory = TEMP_DIR;
		$refl = new \ReflectionProperty('\Nette\Application\UI\Presenter', 'onShutdown');
		// @ to suppress deprecation warning from Tracy on PHP >=8.4, see https://github.com/nette/tracy/pull/587
		$file = @Debugger::log(new Kdyby\Autowired\MissingServiceException('Missing service blabla', $refl));

		Assert::match('%A%<h2%a?%>%A?%<a%a% class="tracy-toggle">Autowired</a>%A?%</h2>%A%', Nette\Utils\FileSystem::read($file));
	}

}

(new ExtensionTest())->run();
