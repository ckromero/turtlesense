<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/* Parser - process

  STEP1- Sensor Registrations
  - Each line gets parsed into a key/value pair.
  - If the expected label is not contained in the key, the file is assumed bad and a flag is set.
  - If the expected value fails its regex pattern, the file is assumed bad and a flag is set.
    
  STEP 2
  - If the file is error free, the database is checked to see if that file's event_date and sensor_id already exist in tblNESTS.
    If it already exists, the data is updated to NESTS, SENSORS, and COMMUNICATORS after an insert to EVENTS. 
    If it does not exist, the file data is inserted (same set of tables) and the file is moved to its "processed" directory.
  - If the file is flagged as bad, all database actions are skipped and the file is moved to malformed_reports.
  - In all cases a log will record the outcome.
  
  So if there is any data missing, misspelled, or out of place in the file, the entire file is rejected. 
  Rejected files can be found in the problem directory. Check the log for the issue. 
  The file can be edited and put back into the reports folderto be read again by the parser.
  You could use this same technique to make a change to an existing report if that were ever necessary. 
 
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
      
      $array_of_lines = explode("\r", $txt_file);
      $array_of_lines = array_map('strtolower', $array_of_lines);              
      $array_of_lines = array_map('trim', $array_of_lines);
      
      // log title determines parser
      $abbreviated_logtitle = substr($array_of_lines[0], 0, 6);

      $data_fields = array();
      switch ($abbreviated_logtitle) 
      {          
        case 'regist': 
          $data_fields = $this->parsemodel->parse_SensorRegistration($array_of_lines);
          $data_fields['file_name'] = $file_name;
          break;
        
        case 'report':        
          $data_fields = $this->parsemodel->parse_SensorReport($array_of_lines);
          $data_fields['file_name'] = $file_name;
          break;
        
        default:        
          $data_fields['file_name'] = $file_name;
          $data_fields['file_format_error'] = "File contains unknown event type.";
          break;    
      }

      // process, log, and archive
      if (isset($data_fields['file_format_error'])) {      
        $this->parsemodel->logFailure($data_fields); 
        $this->parsemodel->archiveBadReport($data_fields); 
      }
      else {
        $this->doDatabaseActions($data_fields);
        $this->parsemodel->logSuccess($data_fields); 
        $this->parsemodel->archiveGoodReport($data_fields); 
      }
    } 
	}
	
	
  function doDatabaseActions($data_fields)
  {
    $event_type = $data_fields['event_type'];
    
    switch ($event_type)
    {
      case 'sensor registration':
        
        $regdate  = $data_fields['event_datetime'];
        $sensorid = $data_fields['sensor_id'];     
        $nest = $this->parsemodel->getNestByDateSensorId($regdate, $sensorid);
        
        if ($nest and $nest->registration_date < $regdate) {
        
          /* It's an update to a nest, registered earlier today. Update the nest, then insert the event, 
            then update the sensor and comm unit but only if they exist, else insert a new record*/
                            
          // update nest
          $data_fields['nest_id'] = $nest->nest_id;        
          $this->parsemodel->updateNest($data_fields);
          
          // insert event
          $this->parsemodel->insertEvent_SensorRegistration($data_fields);
          
          // update sensor if exists, else insert
          $sensor_exists = $this->parsemodel->sensorExists($data_fields);
          $sensor_exists ? $this->parsemodel->updateSensor($data_fields) : $this->parsemodel->insertSensor($data_fields);   

          // update comm if exists, else insert
          $comm_exists = $this->parsemodel->commExists($data_fields);
          $comm_exists ? $this->parsemodel->updateComm($data_fields) : $this->parsemodel->insertComm($data_fields);   

        } 
        else {
        
          /* This is a new nest, which means it's a new combination of sensor_id and  event_datetime.
          Insert the new nest, then the event, then update the sensor if it exists, else insert it,
          and then update the comm if it exists, else insert it */
          
          $data_fields['nest_id'] = $this->parsemodel->insertNest($data_fields);
                      
          // insert event
          $this->insertEvent_SensorRegistration($data_fields);
          
          // insert sensor, if it doesn't exist
          $sensor_exists = $this->sensorExists($data_fields);
          $sensor_exists ? $this->updateSensor($data_fields) : $this->insertSensor($data_fields);
               
          // insert comm, if it doesn't exist 
          $comm_exists = $this->commExists($data_fields);
          $comm_exists ? $this->updateComm($data_fields) : $this->insertComm($data_fields);   
        }    
        break;
      
      case 'sensor report':
  
        break;
      
      default:
        break;
    }
  }


}
/* EOF */