<?php
declare(strict_types=1);

namespace KdybyTests\Autowired\DeprecationsFixtures;

use Kdyby;
use Kdyby\Autowired\Attributes\Autowire;
use Nette;


class NonTypedPropertyPresenter extends Nette\Application\UI\Presenter
{

	use Kdyby\Autowired\AutowireProperties;

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
	 * @var SampleService
	 */
	#[Autowire]
	public $service;

}
