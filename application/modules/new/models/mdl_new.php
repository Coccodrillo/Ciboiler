<?php

class Mdl_new extends MY_Model {

	function Mdl_racuni() {
		parent::__construct();
	}

	protected $_table = 'emails';

	public function test()
	{
		return array("text" => "test");
	}

}
