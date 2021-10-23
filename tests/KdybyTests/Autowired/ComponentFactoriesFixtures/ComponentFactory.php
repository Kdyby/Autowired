<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

interface ComponentFactory
{

	public function create(): SillyComponent;
}
