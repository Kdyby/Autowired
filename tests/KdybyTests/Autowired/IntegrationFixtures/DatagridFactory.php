<?php

namespace KdybyTests\Autowired\IntegrationFixtures;

use Kdyby;
use Nette;

interface DatagridFactory
{

	public function create(): DatagridComponent;
}
