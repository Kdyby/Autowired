<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

interface SampleServiceFactory
{

	public function create(string $name, ?string $secondName = NULL): SampleService;

}
