<?php
declare(strict_types=1);


namespace KdybyTests\Autowired\PropertiesFixtures\UseExpansion;


use KdybyTests\Autowired\PropertiesFixtures\SampleService;


class ImportedService
{

	public function create(string $name, string $secondName): SampleService
	{
		return new SampleService($name, $secondName);
	}

}
