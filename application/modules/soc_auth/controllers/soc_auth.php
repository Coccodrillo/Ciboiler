<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Soc_auth extends MY_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->config("ci_opauth");
	}

	public function index(){
        redirect($this->lang->lang().'/soc_auth/login');
	}

    public function login($choice=null){
        //Comprobate if the user request a strategy
        if(!$choice){
            $ci_config = $this->config->item('opauth_config');
            $arr_strategies = array_keys($ci_config['Strategy']);

            echo("Please, select an Oauth provider:<br />");
            echo("<ul>");
            foreach($arr_strategies AS $strategy){
                echo("<li><a href='".base_url().$this->lang->lang()."/soc_auth/login/".strtolower($strategy)."'>Login with ".$strategy."</li>");
            }
            echo("</ul>");
        }
        else{
            //Run login
            $this->load->library('Opauth/Opauth', $this->config->item('opauth_config'), false);
            $this->opauth->run();
        }
    }

    function authenticate(){
        //Create authenticate logic
        $response = unserialize(base64_decode( $_POST['opauth'] ));
        echo("<pre>");
        print_r($response);
        echo("</pre>");
    }

    public function logout(){
        //Create logout logic.
    }

}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */
