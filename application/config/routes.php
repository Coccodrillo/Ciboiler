<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$route['default_controller'] = "welcome";
$route['404_override'] = '';

// example: '/en/about' -> use controller 'about'
$route['^fr/(.+)$'] = "$1";
$route['^sl/(.+)$'] = "$1";
$route['^en/(.+)$'] = "$1";

// '/en' and '/fr' -> use default controller
$route['^fr$'] = $route['default_controller'];
$route['^sl$'] = $route['default_controller'];
$route['^en$'] = $route['default_controller'];




/* End of file routes.php */
/* Location: ./application/config/routes.php */
