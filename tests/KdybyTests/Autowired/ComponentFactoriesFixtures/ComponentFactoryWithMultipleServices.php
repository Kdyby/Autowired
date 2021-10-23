<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

interface ComponentFactoryWithMultipleServices
{

	public function create(): SillyComponent;
}

