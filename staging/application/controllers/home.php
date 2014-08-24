<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->model('log', 'logmodel');
		//$this->headerScripts[] = add_jscript('myscript');	
	}
	
	public function index()
	{
		$data = array('pagetitle' => "Turtle Sense",
					        'centerview' => 'home');

		
		$this->load->view('theme', $data);
	}
}

/* EOF */