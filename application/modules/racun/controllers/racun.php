<?php

#require_once (APPPATH.'core/racun2.php');

class Racun extends MY_Controller

{

	public function index()
	{
		echo "dvojka";
		$this->_render(array('body' => 'računi'));
	}

	public function drugi()
	{
		$this->load->model('mdl_racuni', 'racuni');

		$coco = $this->racuni->get_all();

		$this->_render(array('body' => $coco));

	}
}

// end of file racuni.php
