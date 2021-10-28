<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

final class GenericFactory
{

	/**
	 * @template T of object
	 * @param class-string<T> $type
	 * @return T
	 */
	public function create(string $type): object
	{
		return new $type();
	}

}
