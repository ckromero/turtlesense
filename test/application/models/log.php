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
					
	function writeToDeviceLog($data_fields) 
	{
    $logEntry_lines     = $data_fields['logEntry_lines'];
    $logEntry_filename  = basename($data_fields['file_name']);
    
    if (strpos($logEntry_filename, '.') === false) {
      $logEntry_filename = str_replace('txt', '.txt', $logEntry_filename);
    } 
    
    $filepath = $this->config->item('reports_processed_dir').'/'.$logEntry_filename;
    
    $message  = "\n";
    
    if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
    {
      return FALSE;
    }
    
    foreach ($logEntry_lines as $line) {
      $message .= $line."\n";
    }	
    	
    flock($fp, LOCK_EX);
    fwrite($fp, $message);
    flock($fp, LOCK_UN);
    fclose($fp);
    
    @chmod($filepath, FILE_WRITE_MODE);
    return TRUE;
	}
					
  function logSuccess($data_fields)
  {    
    $this->writeToApplicationLog(strtoupper($data_fields['event_type']).': '.$data_fields['sensor_id'].' - '.$data_fields['event_datetime']. '. Loaded to the database.');	
  }


  function logFailure($data_fields)
  {      
    $filename = basename($data_fields['file_name']);

    if (isset($data_fields['file_format_error'])) { // abstract this to error_type, if other types are required
    
      // write to log - as of now, on one type of error - file_format_error. All should be aborted.
      $this->writeToApplicationLog('ERROR - '.$data_fields['file_format_error'].' Moving '.$filename .' to '.$this->config->item('reports_malformed_dir').'. Aborting process.');	
    }
    // send me email once a day! using a cookie
  }


  function deleteLogFile($file)
  {
		if (is_file($file))
		{
			unlink($file);
			return true;
		}	
		return false;							    
  }
  
					
}		
/* EOF */

