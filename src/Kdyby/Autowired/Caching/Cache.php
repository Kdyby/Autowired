<?php
declare(strict_types=1);

namespace Kdyby\Autowired\Caching;

use Kdyby\Autowired\AutowireComponentFactories;
use Kdyby\Autowired\AutowireProperties;
use Nette;

final class Cache
{

	private Nette\Caching\Cache $cache;

	/**
	 * @var class-string<Nette\Application\UI\Component>
	 */
	private string $componentClass;

	private string $containerFile;

	/**
	 * @param Nette\Caching\Cache $cache
	 * @param class-string<Nette\Application\UI\Component> $componentClass
	 * @param string $containerFile
	 */
	public function __construct(Nette\Caching\Cache $cache, string $componentClass, string $containerFile)
	{
		$this->cache = $cache;
		$this->componentClass = $componentClass;
		$this->containerFile = $containerFile;
	}

	public function load(): mixed
	{
		return $this->cache->load($this->getCacheKey());
	}

	public function save(mixed $value): void
	{
		$this->cache->save(
			$this->getCacheKey(),
			$value,
			[
				Nette\Caching\Cache::FILES => $this->getFileDependencies(),
			],
		);
	}

	/**
	 * @return mixed[]
	 */
	private function getCacheKey(): array
	{
		return [$this->componentClass, $this->containerFile];
	}

	/**
	 * @return list<string>
	 */
	private function getFileDependencies(): array
	{
		/** @var list<class-string> $nettePresenterParents */
		$nettePresenterParents = class_parents(Nette\Application\UI\Presenter::class);
		assert(is_array($nettePresenterParents));
		$ignoreClasses = $nettePresenterParents + ['ui' => Nette\Application\UI\Presenter::class];
		$ignoreTraits = [AutowireProperties::class, AutowireComponentFactories::class];

		/** @var list<class-string> $componentParents */
		$componentParents = class_parents($this->componentClass);
		assert(is_array($componentParents));

		$classes = array_values(array_diff($componentParents + ['me' => $this->componentClass], $ignoreClasses));
		foreach ($classes as $class) {
			/** @var list<class-string> $uses */
			$uses = class_uses($class);
			assert(is_array($uses));
			$classes = array_merge($classes, array_diff($uses, $ignoreTraits));
		}

		$files = array_map(
			fn (string $class): string => (string) (new \ReflectionClass($class))->getFileName(),
			$classes,
		);

		$files[] = $this->containerFile;

		return array_values(array_unique($files));
	}

}
