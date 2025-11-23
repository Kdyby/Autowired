<?php
declare(strict_types=1);

use Composer\InstalledVersions;
use Composer\Semver\VersionParser;

$config = ['parameters' => ['ignoreErrors' => []]];

if (InstalledVersions::satisfies(new VersionParser(), 'nette/application', '<3.2')) {
	$config['parameters']['ignoreErrors'][] = [
		'message' => '~^Call to function method_exists\\(\\) with Nette\\\\Application\\\\UI\\\\Presenter and \'getContext\' will always evaluate to true\\.$~',
		'path' => __DIR__ . '/../../src/Kdyby/Autowired/AutowireComponentFactories.php',
		'count' => 6,
	];
}

return $config;
