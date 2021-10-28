<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

class FactoryWithDisabledAutowiring
{

	public function create(): SampleService
	{
		return new SampleService(self::class);
	}

}
