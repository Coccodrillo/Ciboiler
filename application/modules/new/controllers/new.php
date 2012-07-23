<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Newcontroller extends MY_Controller {

	function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		$this->_parser($data);
	}

}
/* End of file inbox.php */
