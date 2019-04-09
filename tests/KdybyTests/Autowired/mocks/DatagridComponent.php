<?php

namespace KdybyTests\Autowired;

use Kdyby;
use Nette;

class DatagridComponent extends Nette\Application\UI\Component
{

}



interface IDatagridFactory
{

	/** @return DatagridComponent */
	public function create();
}
