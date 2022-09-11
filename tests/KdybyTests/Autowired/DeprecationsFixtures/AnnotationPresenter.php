<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\DeprecationsFixtures;

use Kdyby;
use Nette;


class AnnotationPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;

	/**
	 * @autowire
	 */
	public SampleService $typedService;

}
