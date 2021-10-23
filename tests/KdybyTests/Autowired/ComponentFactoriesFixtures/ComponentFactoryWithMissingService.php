<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

interface ComponentFactoryWithMissingService
{

	public function create(): SillyComponent;
}

