<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\PropertiesFixtures;

use Kdyby;
use Kdyby\Autowired\Attributes\Autowire;
use Nette;

class BaseControl extends Nette\Application\UI\Control
{

	use Kdyby\Autowired\AutowireProperties;

	#[Autowire]
	public SampleService $baseService;

}
