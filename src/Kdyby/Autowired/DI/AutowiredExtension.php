<?php
declare(strict_types=1);

namespace Kdyby\Autowired\DI;

use Nette;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\PhpGenerator as Code;



/**
 * @author Filip Procházka <filip@prochazka.su>
 */
class AutowiredExtension extends Nette\DI\CompilerExtension
{

	public static function register(Nette\Configurator $configurator): void
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler): void {
			$compiler->addExtension('autowired', new AutowiredExtension());
		};
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = (array) $this->getConfig();

		$storage = $builder->addDefinition($this->prefix('cacheStorage'), new ServiceDefinition())
			->setType(Nette\Caching\Storage::class)
			->setAutowired(FALSE);

		$storage->setFactory(is_string($config['cacheStorage'])
			? new Nette\DI\Definitions\Statement($config['cacheStorage'])
			: $config['cacheStorage']);
	}

	public function afterCompile(Code\ClassType $class): void
	{
		$this->initialization->addBody('Kdyby\Autowired\Diagnostics\Panel::registerBluescreen();');
	}

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Nette\Schema\Expect::structure([
			'cacheStorage' => Nette\Schema\Expect::string('@' . Nette\Caching\Storage::class),
		]);
	}

}
