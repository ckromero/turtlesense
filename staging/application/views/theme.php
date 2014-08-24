<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

$this->load->view($this->config->item('theme_path').'header');
	
if(isset($centerview)) {

	$this->load->view($centerview);
	
} else {

	$this->load->view($this->config->item('theme_path').'home');
}

$this->load->view($this->config->item('theme_path').'footer');
	
 
/*EOF*/


