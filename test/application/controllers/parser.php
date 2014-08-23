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
 
 
 Potential Confusion:
 
  Logs refer to two things: typical system log, and the device log that's ftp'd
  Reports refer to two things: any device log, and the specific device log that contains records.
 
 
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
    $logEntry_typecode = '';

    foreach ($files as $file_name) {                

      $txt_file = trim(file_get_contents($file_name));      
      
      // There can be several reports in one report log, each terminated by '--end of report--'
      // If '--end of report--' is not found, it's a registration log file, so split on \r\r
      if (strpos($txt_file, '--end of report--') !== false) {
        $logEntries_arr = explode("--end of report--", trim($txt_file));  
      } else {
        $logEntries_arr = explode("\r\r", trim($txt_file));    
      }
      
      // In case files use \n\n, split on newline instead
      if ( count($logEntries_arr) == 1) {
        $logEntries_arr = explode("\n\n", $txt_file);
      } 
      $logEntries_arr = array_map('trim', $logEntries_arr); // trims all elements
      $logEntries_arr = array_filter($logEntries_arr); // removes empty elements
  
//echo '<pre>';print_r($logEntries_arr);
 
      foreach ($logEntries_arr as $logEntry) {        
  
        $logEntry_lines = explode("\r", $logEntry);

        if ( count($logEntry_lines) == 1) {  
          // file doesn't use return character, so split on newline
          $logEntry_lines = explode("\n", $logEntry);
        } 

//echo '<pre>';print_r($logEntry_lines);
     
        $logEntry_lines = array_map('trim', $logEntry_lines);      
      
        // log title determines which parser to call
        $logEntry_typecode = strtoupper(substr($logEntry_lines[0], 0, 3));

//echo $logEntry_typecode;
    
        $data_fields = array();
        switch ($logEntry_typecode) 
        {          
          case 'REG': // registration
            $data_fields = $this->parsemodel->parse_NestRegistration($logEntry_lines);
            $data_fields['file_name']      = $file_name;
            $data_fields['logEntry_lines'] = $logEntry_lines; // to re-create log file   

//echo  '<pre>';print_r($data_fields);   exit;      

            break;
          
          case 'REP': // report    
            $data_fields = $this->parsemodel->parse_NestReport($logEntry_lines);
            $data_fields['file_name']      = $file_name; 
            $data_fields['logEntry_lines'] = $logEntry_lines;
            // capture report_filename_id to get report_id for inserts to RECORDS
            $report['report_filename_id']  = $data_fields['report_filename_id'];

//echo  '<pre>REP<br>';print_r($data_fields);   exit;      

            break;
          
          case 'REC': // record  
            $data_fields = $this->parsemodel->parse_NestRecords($logEntry_lines);
            $data_fields['file_name'] = $file_name;   
            $data_fields['logEntry_lines'] = $logEntry_lines;         
            if (isset($report)){
              $data_fields['report_filename_id'] = $report['report_filename_id'];
            } 
            break;
          
          case 'TUR': // turtlesense       
            // end of file
            break;
          
          default:        
            $data_fields['file_name'] = $file_name;
            $data_fields['file_format_error'] = "File contains unknown event type.";
            break;    
        }

        if (isset($data_fields['file_format_error'])) {  
        
            // Failure. If a single datum is out of place, log the issue and skip    
            $this->parsemodel->moveMalformedLogFile($data_fields); 
            $this->logmodel->logFailure($data_fields); 
            echo 'failure - malformed log report - moved to malformed reports archive<br>';
        }
        else if ($logEntry_typecode == 'REG') {
        
          // REGISTRATION - skip entry if event exists
          $event_exists = $this->parsemodel->eventExists($data_fields);
          if ($event_exists) {
          
            $this->logmodel->logDuplicateEvent($data_fields);    
          }
          else {

//echo  '<pre>';print_r($data_fields);   exit; 
            $this->_processNestRegistration($data_fields);           
          } 
          
        } else if ($logEntry_typecode == 'REP') {

            // REPORT - look at report (and records) even if event already exists    
            $event_exists = $this->parsemodel->eventExists($data_fields);
            if ($event_exists) {
              
              //$this->logmodel->logDuplicateEvent($data_fields);    
              //echo 'duplicate - entry skipped - '.$data_fields['sensor_id'].' '. $data_fields['event_datetime'].'<br>'; 
            }          
            
            // ! YOUR ARE HERE
            $this->_processNestReport($data_fields);              
           

        } else if ($logEntry_typecode == 'REC') {
          
          // RECORDS
          
          // get report_id from REPORTS based on record_filename_id (have to parse for nest_id and sensor_id)
          // insert RECORDS via loop
          
            /*
                [240] => Array
                    (
                        [record_num] => 00EF
                        [temperature] => 01FA
                        [x] => FDBF
                        [y] => FE47
                        [z] => 03C3
                        [cnt] => 734E
                        [max] => 0009
                        [bin_a] => 0554
                        [bin_b] => 0D03
                        [bin_c] => 0BF7
                        [bin_d] => 14AB
                        [bin_e] => 21FE
                        [bin_f] => 1959
                        [bin_g] => 0471
                        [bin_h] => 0072
                        [bin_i] => 001B
                        [bin_j] => 0000
                    )
            
                [devicemsg-241] => NEW SETTINGS LOADED. STARTS AFTER THIS REPORT
                [devicemsg-242] => POWERING UP
                [event_type] => nest record
            */
          
        }   
      } 
    } 
    
    // DELETE, RECONSTRUCT, or MOVE log file ???
    //$this->logmodel->deleteLogFile($data_fields['file_name']);

    switch ($logEntry_typecode)
    {
      case 'REG':
        $this->logmodel->deleteLogFile($data_fields['file_name']);
        break;
      
      case 'REP':
        break;
      
      case 'REC':
        break;
      
    }
	}
		  
  function _processNestRegistration($data_fields)
  {    
    $this->parsemodel->dba_nestRegistration($data_fields);
    $this->logmodel->logSuccess($data_fields); 
    $this->logmodel->reconstructRegistrationFile($data_fields);          
  }
  

  function _processNestReport($data_fields)
  {   
    $this->_processNestRegistration($data_fields);
    // ??? $this->logmodel->reconstructRegistrationFile($data_fields);
    
echo 'next, insert report';exit;

/*
    $this->_doDBA_nestReport($data_fields);
    $this->logmodel->logSuccess($data_fields);           
    //$this->logmodel->reconstructReportFile($data_fields);
    //$this->logmodel->deleteLogFile($data_fields['file_name']);
    echo 'success - loaded report to database - '.$data_fields['sensor_id'].' '. $data_fields['event_datetime'].'<br>';  
*/   
    
  }
/* MS:: remove this  
  function _processDuplicateEvent($data_fields)
  {
    $this->logmodel->logDuplicateEvent($data_fields);    
    echo 'duplicate - entry skipped - '.$data_fields['sensor_id'].' '. $data_fields['event_datetime'].'<br>';
  }
*/


}
/* EOF */




 