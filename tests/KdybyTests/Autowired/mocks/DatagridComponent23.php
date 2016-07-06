<?php

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;

class DatagridComponent extends Nette\Application\UI\PresenterComponent
{

	public function __construct()
	{
		parent::__construct();
	}

}



interface IDatagridFactory
{

	/** @return DatagridComponent */
	public function create();
}
