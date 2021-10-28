<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\IntegrationFixtures;

interface DatagridFactory
{

	public function create(): DatagridComponent;

}
