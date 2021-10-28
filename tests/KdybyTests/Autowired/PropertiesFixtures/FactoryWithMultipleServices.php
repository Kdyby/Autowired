<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

class FactoryWithMultipleServices
{

	public function create(): SampleService
	{
		return new SampleService(self::class);
	}

}
