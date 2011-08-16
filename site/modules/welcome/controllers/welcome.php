<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends MX_Controller {

	public function index()
	{
		$this->load->view('welcome_message');
	}

}

/* End of file welcome.php */
/* Location: ./application/modules/welcome/controllers/welcome.php */
