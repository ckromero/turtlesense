<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Log extends CI_Model {
		
	function __construct()
	{
		parent::__construct();
		$this->load->helper('file');
	}			
	
	function writeToApplicationLog($msg='') 
	{
		$filepath = $this->config->item('logs_parser_dir').'/'.date('Y-m-d').'.php';
				
		$message  = '';

		if ( ! file_exists($filepath))
		{
			$message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}
		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
		{
			return FALSE;
		}
		$message .= "\n".date('Y-m-d H:i:s')."\n".$msg."\n";
				
		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($filepath, FILE_WRITE_MODE);
		return TRUE;
	}
					
}		
/* EOF */

