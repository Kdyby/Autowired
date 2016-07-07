<?php

/**
 * This file is part of the Kdyby (http://www.kdyby.org)
 *
 * Copyright (c) 2008 Filip Procházka (filip@prochazka.su)
 *
 * For the full copyright and license information, please view the file license.md that was distributed with this source code.
 */

namespace KdybyTests\Autowired;

use Nette\DI\ContainerBuilder;
use Nette\DI\PhpGenerator;
use Tester;

if (@!include __DIR__ . '/../../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

// configure environment
Tester\Environment::setup();
class_alias('Tester\Assert', 'Assert');
date_default_timezone_set('Europe/Prague');

// create temporary directory
define('TEMP_DIR', __DIR__ . '/../tmp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
Tester\Helpers::purge(TEMP_DIR);


$_SERVER = array_intersect_key($_SERVER, array_flip([
	'PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv']));
$_SERVER['REQUEST_TIME'] = 1234567890;
$_ENV = $_GET = $_POST = [];

function id($val) {
	return $val;
}

function run(Tester\TestCase $testCase) {
	$testCase->run();
}



abstract class ContainerTestCase extends \Tester\TestCase
{

	/**
	 * @param \Nette\DI\ContainerBuilder $builder
	 * @param string $className
	 * @return \Nette\DI\Container
	 */
	protected function compileContainer(ContainerBuilder $builder, $className = NULL)
	{
		if (class_exists('Nette\DI\PhpGenerator')) {
			$generator = new PhpGenerator($builder);
			$classes = $generator->generate($className = ($className ?: 'Container'));

		} else {
			$classes = $builder->generateClasses();
			$classes[0]->setName($className = ($className ?: 'Container'))
				->setExtends('Nette\DI\Container')
				/*->addMethod('initialize')*/;
		}

		$code = implode('', $classes);
		$ns = 'DIC_' . md5($code);
		$className = $ns . '\\' . $className;

		file_put_contents($file = TEMP_DIR . '/code.' . urlencode($className) . '.php', "<?php\nnamespace $ns;\nuse Nette, KdybyTests;\n\n$code");
		require_once $file;

		$container = new $className();
		/*$container->initialize();*/

		return $container;
	}

}
