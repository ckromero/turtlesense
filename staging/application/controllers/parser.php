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
    // Last modification date that was stored after the last batch of parsed files
    $lastparse_filemtime = $this->logmodel->read_lastparse_filemtime();
    
    // Get files with a mod date newer than $lastparse_filemtime 
    $files = $this->parsemodel->getFiles($lastparse_filemtime);
    
 
//echo '<pre>Files to parse:<br>';print_r($files);

//echo "Last batch: ". date("Y-m-d H:i:s", $lastparse_filemtime)."<br><br>";

    $logEntry_typecode = '';
 
    if ($files) {
  
      $newest_filemtime = $lastparse_filemtime;
      
      foreach ($files as $file_name) {                       

        // compare file to newest file in this batch so far
        $newest_filemtime = (filemtime($file_name) > $newest_filemtime) ? filemtime($file_name) : $newest_filemtime;

//echo "This  file: ".date("Y-m-d H:i:s", $newest_filemtime)."<br>";
        
        $txt_file = trim(file_get_contents($file_name));      
        
        /* There can be several reports in one file, each terminated by '--end of report--'
         * If '--end of report--' is not found, it's a registration file, so split on \r\r */
         
        if (strpos($txt_file, '--end of report--') !== false) {
          $logEntries_arr = explode("--end of report--", trim($txt_file));  
        } else {
          $logEntries_arr = explode("\r\r", trim($txt_file));    
        }
        
        // In case files use \n\n, split on newline instead
        if ( count($logEntries_arr) == 1) {
          $logEntries_arr = explode("\n\n", $txt_file);
        } 
        
        // remove empty elements
        $logEntries_arr = array_filter($logEntries_arr); 

        // trim each element
        $logEntries_arr = array_map('trim', $logEntries_arr); 
        
//echo '<pre>Array of log entries to process from this file ($logEntries_arr): <br>';print_r($logEntries_arr);exit;
   
        foreach ($logEntries_arr as $logEntry) {        
    
          $logEntry_lines = explode("\r", $logEntry);
  
          if ( count($logEntry_lines) == 1) {  
            // file doesn't use return character, so split on newline
            $logEntry_lines = explode("\n", $logEntry);
          } 
            
//echo '<pre>Next log to process ($logEntry_lines):<br>';print_r($logEntry_lines);exit;
       
          $logEntry_lines = array_map('trim', $logEntry_lines); 
        
          // log title determines which parser to call
          $logEntry_typecode = strtoupper(substr($logEntry_lines[0], 0, 3));
 
          $data_fields = array();
          switch ($logEntry_typecode) 
          {          
            case 'REG': 
              //  REGISTRATION
              $data_fields = $this->parsemodel->parse_NestRegistration($logEntry_lines);
              
              if (!isset($data_fields['file_format_error'])) {                
                $eventexists = $this->parsemodel->eventExists($data_fields);               
                if (!$eventexists) {               
                  $data_fields['file_name'] = $file_name;  
//echo  '<pre>-- REGISTRATION -- Parsed data<br>';print_r($data_fields);  exit; 
                  $this->_processNestRegistration($data_fields); 
                }
              }
              else {
                $this->logmodel->logFailure($data_fields);
              }
              break;
              
                            
            case 'REP':
              //  REPORT    
              $data_fields = $this->parsemodel->parse_NestReport($logEntry_lines);

              if (!isset($data_fields['file_format_error'])) {  
                $reportexists = $this->parsemodel->reportExists($data_fields);                
                if (!$reportexists) {                 
                  $data_fields['file_name']       = $file_name; 
                  $data_fields['report_datetime'] = $data_fields['event_datetime'];
 
//echo "<pre>"; print_r($data_fields); exit;
 
 
                                    
                  // capture header values from the report for records, before giving up data_fields[] to records
                  $report['num_records']        = $data_fields['num_records'];
                  $report['sensor_id']          = $data_fields['sensor_id'];
                  $report['report_datetime']    = $data_fields['report_datetime'];
                  $report['report_starttime']   = $data_fields['report_starttime'];
                  $report['secs_per_rec']       = $data_fields['secs_per_rec'];
                  
                  

//echo  '<pre>-- REPORT -- Parsed data<br>';print_r($data_fields); exit;   
                               
                  $reportid  = $this->_processNestReport($data_fields);                   
                  $nestreport = $this->parsemodel->getReportById($reportid);
                  $report['nest_id'] = $nestreport->nest_id;
                  $report['report_id'] = $nestreport->report_id;
                  
                  // element 11 will always be empty, but check anyway for now
                  if(empty($logEntry_lines[11])){
                  
                    $recordEntry_lines = array_slice($logEntry_lines,12);
                    
//echo '<pre>Record entry lines ($recordEntry_lines):<br>';print_r($recordEntry_lines);exit;
                                         
                    $records = $this->parsemodel->parse_NestRecords($recordEntry_lines);
                    $records['file_name']          = $file_name; 
                    $records['report_id']          = $report['report_id'];        
                    $records['num_records']        = $report['num_records'];  
                    $records['sensor_id']          = $report['sensor_id'];  
                    $records['event_datetime']     = $report['report_datetime'];  
                    $records['report_datetime']    = $report['report_datetime'];  
                    $records['report_starttime']   = $report['report_starttime'];
                    $records['secs_per_rec']       = $report['secs_per_rec'];
                    $records['nest_id']            = $report['nest_id'];  
                          
//echo  '<pre>-- RECORDS -- Parsed data<br>';print_r($records);exit;
  
                    // we dont have to test existence since done at the report level
                    $this->_processNestRecords($records); 
                      
                   } 
                   else {
                    $data_fields['file_format_error'] = "Expected empty element in logEntry_lines array. See case REP in parser switch.";
                    $this->logmodel->logFailure($data_fields);                     
                   }                                                               
                }
              }
              else {
                $data_fields['file_name']  = $file_name; 
                $this->logmodel->logFailure($data_fields);
              }
              break;
              
            default:        
              $data_fields['file_name'] = $file_name;
              $data_fields['file_format_error'] = "File contains unknown event type.";
              break;    
          }
  

        }//foreach entry
 
        // Save to a file, the newest modification date from this batch of parsed files
        //$this->logmodel->write_lastparse_filemtime($newest_filemtime);
        // 2000-01-01 = 946713600
        $this->logmodel->write_lastparse_filemtime('1388736000'); 
//echo "Saving file's date: ". date("Y-m-d H:i:s", $newest_filemtime)."<br><br>";


      }//foreach file
    } 
    else {      
      echo "No new files to read.<br>";
    }
    echo "<br>THE END";
	}
		  
  function _processNestRegistration($data_fields)
  {    
    // override event_type, since could have been passed by a "nest report"
    $data_fields['event_type'] = 'nest registration';
    $eventexists = $this->parsemodel->eventExists($data_fields);
    if (!$eventexists) {
      $this->parsemodel->dba_nestRegistration($data_fields);
      $this->logmodel->logSuccess($data_fields);     
    }
  }  

  function _processNestReport($data_fields)
  {   


    $eventexists = $this->parsemodel->eventExists($data_fields);
    if (!$eventexists) { 
      $this->_processNestRegistration($data_fields); 
    } 


//echo "<pre>"; print_r($data_fields); exit;


    $report_id = $this->parsemodel->dba_nestReport($data_fields);
    $this->logmodel->logSuccess($data_fields);     
    return $report_id;        
  }

  function _processNestRecords($records)
  {       
    $numrecs = $records['num_records'];
    
    

    if ($numrecs) {

      $x=1;
      while ($x < $numrecs +1 ) {
      
        $data_fields = $records[$x];
        //$data_fields = array_map('hexdec', $data_fields);
        $data_fields['temperature']     = $data_fields['temperature']/25.6;
        $data_fields['report_id']       = $records['report_id'];                      
        $data_fields['nest_id']         = $records['nest_id'];                                            
        $data_fields['report_id']       = $records['report_id'];      
        // first record begins with zero, so set each as num + 1                    
        $data_fields['record_num']      = $data_fields['record_num'] + 1;      
        $data_fields['record_datetime'] = date('Y-m-d H:i:s', 
                                            strtotime(
                                              $records['report_starttime']) 
                                              + ($records['secs_per_rec'] * $data_fields['record_num'] ));                          
        $x++;
        
//echo '<pre>Fields of data for one RECORD:<br>';print_r($data_fields).'<br>';  exit;      
        $this->parsemodel->dba_nestRecord($data_fields);
      }
      $records['event_type'] = "nest record set";//cludge for log only
      $this->logmodel->logSuccess($records);     
    } 
  }

  
  
  
}
/* EOF */




 