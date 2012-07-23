<?php

class Template extends MY_Controller
{
	function __construct ()
	{
		parent::__construct();
	}

	public function _render($input=null, $_template = "default", $_method="parse")
	{
		$input['_template'] = $_template;
		if (!isset($input['title']))
		{
			$input["title"]="Default title";
		}
		if ($_method=="parse")
		{
			$this->load->library('parser');
			$this->parser->parse("_parser", $input);
		}
		else
		{
			$this->load->view("_parser", $input);
		}
	}
}

// end of file racuni.php
