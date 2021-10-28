<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

class SampleService
{

	/**
	 * @var array<string|null>
	 */
	public array $args;

	public function __construct(string $name, ?string $secondName = NULL)
	{
		$this->args = func_get_args();
	}

}
