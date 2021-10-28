<?php
declare(strict_types=1);

namespace Kdyby\Autowired\Attributes;

use Attribute;


#[Attribute(Attribute::TARGET_PROPERTY)]
final class Autowire
{

	/**
	 * @var class-string
	 */
	private ?string $factory;

	/**
	 * @var array<mixed>
	 */
	private array $arguments;

	/**
	 * @param class-string|null $factory
	 * @param array<mixed> $arguments
	 */
	public function __construct(?string $factory = NULL, array $arguments = [])
	{
		if ($factory === NULL && $arguments !== []) {
			throw new \InvalidArgumentException('Factory must be specified when any arguments are passed in.');
		}
		$this->factory = $factory;
		$this->arguments = $arguments;
	}

	/**
	 * @return class-string|null
	 */
	public function getFactory(): ?string
	{
		return $this->factory;
	}

	/**
	 * @return array<mixed>
	 */
	public function getArguments(): array
	{
		return $this->arguments;
	}

	/**
	 * @return array{}|array{"factory": class-string, "arguments": array<mixed>}
	 */
	public function toArray(): array
	{
		if ($this->factory === NULL) {
			return [];
		}

		return [
			'factory' => $this->factory,
			'arguments' => $this->arguments,
		];
	}

}
