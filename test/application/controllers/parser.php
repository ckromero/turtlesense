<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Parser - process

  STEP1- Sensor Registrations
  - Each line gets parsed into a key/value pair.
  - If the expected label is not contained in the key, the file is assumed bad and a flag is set.
  - If the expected value fails its regex pattern, the file is assumed bad and a flag is set.
    
  STEP 2
  - If the file is error free, the database is checked to see if that file's event_date and sensor_id already exist in NESTS.
    If it already exists, the data is updated to NESTS, SENSORS, and COMMUNICATORS after an insert to EVENTS. 
    If it does not exist, the file data is inserted (same set of tables) and the file is moved to its "processed" directory.
  - If the file is flagged as bad, all database actions are skipped and the file is moved to malformed_reports.
  - In all cases a log will record the outcome.
  
  So if there is any data missing, misspelled, or out of place in the file, the entire file is rejected. 
  Rejected files can be found in the problem directory. Check the log for the issue. 
  The file can be edited and put back into the reports folderto be read again by the parser.
  You could use this same technique to make a change to an existing report if that were ever necessary. 
  
  An event is the same as a log entry for a registration or log entry for a report (set of records from a sensor).
 
 */

class Parser extends CI_Controller {

	function __construct()
	{
		parent::__construct();		
		$this->load->model('parse', 'parsemodel');
		$this->load->model('log', 'logmodel');		
	}
	
	function index()
	{    
    $files = $this->parsemodel->getFiles();

    foreach ($files as $file_name) {                

      $txt_file = trim(file_get_contents($file_name));      
      $array_of_logEntries = explode("\r\r", trim($txt_file));
      
      if ( count($array_of_logEntries) == 1) {  
        // file doesn't use return character, so parse by newline instead
        $array_of_logEntries = explode("\n\n", $txt_file);
      } 
      $array_of_logEntries = array_map('trim', $array_of_logEntries);
 
      foreach ($array_of_logEntries as $logEntry) {        
  
        $logEntry_lines = explode("\r", $logEntry);
        
        if ( count($logEntry_lines) == 1) {  
          // file doesn't use return character, so parse by newline instead
          $logEntry_lines = explode("\n", $logEntry);
        } 
  
        $logEntry_lines = array_map('trim', $logEntry_lines);      
        //$logEntry_lines = array_map('strtolower', $logEntry_lines);
      
        // log title determines parser
        $abbreviated_logtitle = substr($logEntry_lines[0], 0, 6);
  
        $data_fields = array();
        switch ($abbreviated_logtitle) 
        {          
          case 'REGIST': 
            $data_fields = $this->parsemodel->parse_SensorRegistration($logEntry_lines);
            $data_fields['file_name'] = $file_name;
            $data_fields['logEntry_lines'] = $logEntry_lines;
            break;
          
          case 'REPORT':        
            $data_fields = $this->parsemodel->parse_SensorReport($logEntry_lines);
            $data_fields['file_name'] = $file_name;
            $data_fields['logEntry_lines'] = $logEntry_lines;
            break;
          
          default:        
            $data_fields['file_name'] = $file_name;
            $data_fields['file_format_error'] = "File contains unknown event type.";
            break;    
        }
              
        if (isset($data_fields['file_format_error'])) {  
        
            // Failure. Move log file to malformed reports directory    
            $this->parsemodel->moveMalformedLogFile($data_fields); 
            $this->logmodel->logFailure($data_fields); 
            echo 'failure - malformed log report - moved to malformed reports archive<br>';
        }
        else {
        
          $event_exists = $this->parsemodel->eventExists($data_fields);
          if ($event_exists) {
          
            // Duplicate. Skip this log record.
            $this->logmodel->logDuplicate($data_fields); 
            $this->logmodel->writeToDeviceLog($data_fields);
            echo 'duplicate - entry skipped - '.$data_fields['sensor_id'].' '. $data_fields['event_datetime'].'<br>';
          }
          else {
          
            // Success. Make database entry.       
            $this->doDatabaseActions($data_fields);
            $this->logmodel->logSuccess($data_fields);           
            $this->logmodel->writeToDeviceLog($data_fields);
            echo 'success - loaded to database - '.$data_fields['sensor_id'].' '. $data_fields['event_datetime'].'<br>';
          } 
        }       
      } $this->logmodel->deleteLogFile($data_fields['file_name']);
    } 
	}
		
  function doDatabaseActions($data_fields)
  {
    $event_type = $data_fields['event_type'];
    
    switch ($event_type)
    {
      case 'nest registration':
               
        /* Insert the new nest, then the event, then update the sensor if it exists, else insert it,
        and then update the comm if it exists, else insert it */

        $data_fields['nest_id'] = $this->parsemodel->insertNest($data_fields);
                    
        // insert event
        $this->parsemodel->insertEvent_SensorRegistration($data_fields);
        
        // insert sensor, if it doesn't exist
        $sensor_exists = $this->parsemodel->sensorExists($data_fields);
        $sensor_exists ? $this->parsemodel->updateSensor($data_fields) : $this->parsemodel->insertSensor($data_fields);
             
        // insert comm, if it doesn't exist 
        $comm_exists = $this->parsemodel->commExists($data_fields);
        $comm_exists ? $this->parsemodel->updateComm($data_fields) : $this->parsemodel->insertComm($data_fields);     
        break;
      
      case 'sensor report':
  
        break;
      
      default:
        break;
    }
  }


}
/* EOF */