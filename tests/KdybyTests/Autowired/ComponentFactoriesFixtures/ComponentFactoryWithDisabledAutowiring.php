<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\ComponentFactoriesFixtures;

interface ComponentFactoryWithDisabledAutowiring
{

	public function create(): SillyComponent;

}
