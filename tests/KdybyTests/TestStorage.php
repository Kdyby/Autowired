<?php
declare(strict_types=1);

namespace KdybyTests;

use Nette;

final class TestStorage implements Nette\Caching\Storage
{

	/**
	 * @var array<string, array{value: mixed, dependencies: array<string, mixed>}>
	 */
	private array $records = [];

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function read(string $key)
	{
		return $this->records[$key]['value'] ?? NULL;
	}

	public function lock(string $key): void
	{
	}

	/**
	 * @param string $key
	 * @param mixed $data
	 * @param array<string, mixed> $dependencies
	 */
	public function write(string $key, $data, array $dependencies): void
	{
		$this->records[$key] = ['value' => $data, 'dependencies' => $dependencies];
	}

	public function remove(string $key): void
	{
		unset($this->records[$key]);
	}

	/**
	 * @param array<string, mixed> $conditions
	 */
	public function clean(array $conditions): void
	{
		if ($conditions[Nette\Caching\Cache::ALL] === TRUE) {
			$this->records = [];
		}
	}

	/**
	 * @return array<string, array{value: mixed, dependencies: array<string, mixed>}>
	 */
	public function getRecords(): array
	{
		return $this->records;
	}

}
