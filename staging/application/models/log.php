<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Log extends CI_Model {
		
	function __construct()
	{
		parent::__construct();
		$this->load->helper('file');
	}			
	
  function logSuccess($data_fields)
  {    
    $msg = strtoupper($data_fields['event_type']).': '.$data_fields['sensor_id'].' - '.$data_fields['event_datetime']. '. Loaded to the database.';
    $this->_writeToApplicationLog($msg);	
    echo $msg.'<br>';
  }

  function logDuplicateEvent($data_fields)
  {    
    $msg = 'DUPLICATE: '.$data_fields['sensor_id'].' - '.$data_fields['event_datetime']. '. Entry skipped.';
    $this->_writeToApplicationLog($msg);
    echo $msg.'<br>';
  }

  function logFailure($data_fields)
  {      
    $filename = basename($data_fields['file_name']);
    if (isset($data_fields['file_format_error'])) {   
      $msg = 'ERROR - '.$data_fields['file_format_error']. ' Filename: '.$filename .'. Aborting process.';
      $this->_writeToApplicationLog($msg);	
      echo $msg.'<br>';
    }
  }
  
	function read_lastparse_filemtime() 
	{
		$filepath = $this->config->item('logs_parser_dir').'/.lastparse_filemtime';
    return file_get_contents($filepath);
	}
					
	function write_lastparse_filemtime($time='0000-00-00 00:00:00') 
	{
		$filepath = $this->config->item('logs_parser_dir').'/.lastparse_filemtime';
    file_put_contents($filepath, $time, LOCK_EX);
    return TRUE;
	}
					
	function _writeToApplicationLog($msg='') 
	{
		$filepath = $this->config->item('logs_parser_dir').'/'.date('Y-m-d').'.php';				
		$message  = '';
		if ( ! file_exists($filepath)) {
			$message .= "<"."?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); ?".">\n\n";
		}
		if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE)) {
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

