<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/* load the MX_Router class */

class MY_Controller extends MX_Controller {

	public function _render($data=null, $_template='default')
	{
		if (!isset($data['title']))
		{
			$data['title'] = "krneki";
		}
		if (!isset($data['body']))
		{
			$data['body'] = "404";
		}
		$this->load->module('template');
		$this->template->_render($data, $_template, "default");
	}

	public function _body($view, $data)
	{
		return $this->load->view($view, $data, true);
	}

	public function _parser($data=null, $_template='parser')
	{
		$this->load->module('template');
		$this->template->_render($data, $_template, "parse");
	}

}
