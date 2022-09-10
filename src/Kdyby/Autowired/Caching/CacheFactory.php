<?php
declare(strict_types=1);

namespace Kdyby\Autowired\Caching;

use Nette;

final class CacheFactory
{

	private Nette\Caching\Storage $cacheStorage;

	private string $containerFile;

	public function __construct(Nette\Caching\Storage $cacheStorage, string $containerFile)
	{
		$this->cacheStorage = $cacheStorage;
		$this->containerFile = $containerFile;
	}

	public static function fromContainer(Nette\DI\Container $container, ?Nette\Caching\Storage $cacheStorage = NULL): self
	{
		$containerFile = (string) (new \ReflectionClass($container))->getFileName();
		$cacheStorage ??= $container->getByType(Nette\Caching\Storage::class);
		return new self($cacheStorage, $containerFile);
	}

	/**
	 * @param class-string<Nette\Application\UI\Component> $componentClass
	 * @param string $namespace
	 * @return Cache
	 */
	public function create(string $componentClass, string $namespace): Cache
	{
		$cache = new Nette\Caching\Cache($this->cacheStorage, $namespace);
		return new Cache($cache, $componentClass, $this->containerFile);
	}

}
