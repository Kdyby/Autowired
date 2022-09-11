<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\DeprecationsFixtures;

use Kdyby;
use Nette;


class SimplePresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;

}
