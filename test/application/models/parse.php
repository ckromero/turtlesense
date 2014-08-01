<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Parse extends CI_Model {

	function __construct()
	{
		parent::__construct();

    $this->load->model('log', 'logmodel');	
	}			
	
	function getFiles() {
	
	  $files = get_filenames($this->config->item('reports_ts_dir'), TRUE);	  
		return $files;
	}
			
  function parse_SensorRegistration($logEntry_lines = array())
  {              
    $fields = array();
    
    foreach ($logEntry_lines as $label => $value) {
      
      switch ($label) {
        
        case 0:
        //  [0]=> string(50) "REGISTRATION EVENT Date/Time: 2014/06/30, 11:04:42" 
        $fields['event_type'] = 'nest registration';
        $string = str_replace(': ', '::', $value);
        strpos($string, '::') !== false ? $data_array = explode("::", $string) : $this->_setErrorMsg('date',$fields);      
        strpos($data_array[0], 'Date/Time') !== false ? '' : $this->_setErrorMsg('date',$fields);          
        $data_array[1] = str_replace(',', '', $data_array[1]); // strip out comma
        preg_match('/^\d{4}\/\d{2}\/\d{2}\s\d{2}\:\d{2}\:\d{2}/', $data_array[1]) ? $fields['event_datetime'] = $data_array[1] : $this->_setErrorMsg('date',$fields);        
        preg_match('/\d{4}\/\d{2}\/\d{2}/', $data_array[1], $match);
        $fields['event_date'] = $match[0];
        break;
        
        case 1:
        //  [1]=> string(18) "Sensor ID#: aa0003"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('sensor',$fields);      
        strpos($data_array[0], 'Sensor') !== false ? '' : $this->_setErrorMsg('sensor',$fields);
        preg_match('/^\w{2}\d{4}/', $data_array[1]) ? $fields['sensor_id'] = $data_array[1] : $this->_setErrorMsg('sensor',$fields);
        break;
        
        case 2:
        //  [2]=> string(39) "Registration Communicator ID#: h-aa0005"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('comm',$fields);      
        strpos($data_array[0], 'Communicator') !== false ? '' : $fields['file_format_error'] = $this->_setErrorMsg('comm',$fields);
        preg_match('/^\w-\w{2}\d{4}/', $data_array[1]) ? $fields['comm_id'] = $data_array[1] : $this->_setErrorMsg('comm',$fields);
        break;
        
        case 3:
        //  [3]=> string(42) "Nest GPS Location: 3514.7907n, 07531.4702w" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('gps',$fields);             
        strpos($data_array[0], 'GPS') !== false ? '' : $this->_setErrorMsg('gps',$fields);        
        strpos($data_array[1], ', ') !== false ? $data_array = explode(', ', $data_array[1]) : $this->_setErrorMsg('gps',$fields);             
        preg_match('/^\d{4,5}.\d{4}\w/', $data_array[0]) ? $fields['latitude']  = $data_array[0] : $this->_setErrorMsg('gps',$fields);
        preg_match('/^\d{4,5}.\d{4}\w/', $data_array[1]) ? $fields['longitude'] = $data_array[1] : $this->_setErrorMsg('gps',$fields);
        break;
        
        case 4:
        //  [4]=> string(19) "Battery level: 022f"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('battery',$fields);      
        strpos($data_array[0], 'Battery') !== false ? '' : $this->_setErrorMsg('battery',$fields);
        preg_match('/^[0-9A-F]{4}/', $data_array[1]) ? $fields['battery_level'] = $data_array[1] : $this->_setErrorMsg('battery',$fields);
        break;
      } 
    }
    //echo '<pre>';print_r($fields);exit;
    return $fields; 
  }
  
  
  function parse_SensorReport($array = array())
  {
    $fields = array();
    
    foreach ($array as $label => $value) {
      
      switch ($label) {
        
        case 0:
        //  [0]=> string(38) "report: 2014-06-30_aa0003_r-005-01.txt"
        $fields['event_type'] = 'sensor report';
        break;

        case 1:
        //  [1]=> string(18) "sensor id#: aa0003"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split sensor id string into array.";      
        strpos($data_array[0], 'sensor id') !== false ? '' : $fields['file_format_error'] = "Expected 'sensor id' label.";
        preg_match('/^\w{2}\d{4}/', $data_array[1]) ? $fields['sensor_id'] = $data_array[1] : $fields['file_format_error'] = "Value for sensor id failed pattern match.";
        break;

        case 2:
        //  [2]=> string(21) "installed: 2014-06-30"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split installed date string into array.";      
        strpos($data_array[0], 'installed') !== false ? '' : $fields['file_format_error'] = "Expected 'install' label for report event.";
        preg_match('/^\d{4}-\d{2}-\d{2}/', $data_array[1]) ? $fields['sensor_install_date'] = $data_array[1] : $fields['file_format_error'] = "Value for installed date failed pattern match.";
        break;
        
        case 3:
        //  [3]=> string(18) "comm id#: c-aa0002"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split comm id string into array.";      
        strpos($data_array[0], 'comm id') !== false ? '' : $fields['file_format_error'] = "Expected 'comm id' label for report event.";
        preg_match('/^\w{1}\-\w{2}\d{4}/', $data_array[1]) ? $fields['comm_id'] = $data_array[1] : $fields['file_format_error'] = "Value for comm id failed pattern match."; 
        break;
        
        case 4:
        //  [4]=> string(30) "days active & report #: 005-01" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split days active string into array.";      
        strpos($data_array[0], 'days active') !== false ? '' : $fields['file_format_error'] = "Expected 'days active' label for report event.";
        strpos($data_array[1], '-') !== false ? $data_array = explode("-", $data_array[1]) : $fields['file_format_error'] = "Expected dash to split days active and report# from string into array.";      
        preg_match('/^\d{3}/', $data_array[0]) ? $fields['days_active'] = $data_array[0] : $fields['file_format_error'] = "Value for days active failed pattern match."; 
        preg_match('/^\d{2}/', $data_array[1]) ? $fields['report_num'] = $data_array[1] : $fields['file_format_error'] = "Value for report num failed pattern match."; 
        break;

        case 5:
        //  [5]=> string(37) "nest location: 3514.7907n,07531.4702w"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split gps string into array.";              
        strpos($data_array[0], 'nest location') !== false ? '' : $fields['file_format_error'] = "Expected 'nest location' label.";        
        strpos($data_array[1], ', ') !== false ? $data_array = explode(', ', $data_array[1]) : $fields['file_format_error'] = "Expected comma to split the nest location coords into array.";             
        preg_match('/^\d{4,5}.\d{4}\w/', $data_array[0]) ? $fields['latitude']  = $data_array[0] : $fields['file_format_error'] = "Value for nest latitude failed pattern match.";
        preg_match('/^\d{4,5}.\d{4}\w/', $data_array[1]) ? $fields['longitude'] = $data_array[1] : $fields['file_format_error'] = "Value for nest longitude failed pattern match.";
        break;
        
        case 6:
        //  [6]=> string(36) "start date/time: 2014/07/03,23:57:32"  
        $string = str_replace(': ', '::', $value);
        strpos($string, '::') !== false ? $data_array = explode("::", $string) : $fields['file_format_error'] = "Expected colon to split start date/time string into array.";      
        strpos($data_array[0], 'start date/time') !== false ? '' : $fields['file_format_error'] = "Expected 'start date/time' label.";
        $data_array[1] = str_replace(',', '', $data_array[1]); // remove comma from date/time string for php
        preg_match('/^\d{4}\/\d{2}\/\d{2}\d{2}\:\d{2}\:\d{2}/', $data_array[1]) ? $fields['start_date'] = $data_array[1] : $fields['file_format_error'] = "Value for start date/time failed pattern match.";
        break;
        
        case 7:
        //  [7]=> string(37) "report date/time: 2014/07/04,01:57:35"  
        $string = str_replace(': ', '::', $value);
        strpos($string, '::') !== false ? $data_array = explode("::", $string) : $fields['file_format_error'] = "Expected colon to split report date/time string into array.";      
        strpos($data_array[0], 'report date/time') !== false ? '' : $fields['file_format_error'] = "Expected 'report date/time' label.";
        $data_array[1] = str_replace(',', '', $data_array[1]); // remove comma from date/time string for php
        preg_match('/^\d{4}\/\d{2}\/\d{2}\d{2}\:\d{2}\:\d{2}/', $data_array[1]) ? $fields['report_date'] = $data_array[1] : $fields['file_format_error'] = "Value for report date/time failed pattern match.";
        break;

        case 8:
        //  [8]=> string(18) "secs per rec: 0168" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split secs per rec string into array.";              
        strpos($data_array[0], 'secs per rec') !== false ? '' : $fields['file_format_error'] = "Expected 'secs per rec' label for report event.";
        preg_match('/^\d{4}/', $data_array[1]) ? $fields['secs_per_record'] = $data_array[1] : $fields['file_format_error'] = "Value for secs per rec failed pattern match."; 
        break;
        
        case 9:
        //  [9]=> string(15) "# of recs: 0014"  
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split # of recs string into array.";              
        strpos($data_array[0], '# of recs') !== false ? '' : $fields['file_format_error'] = "Expected '# of recs' label.";
        preg_match('/^\d{4}/', $data_array[1]) ? $fields['num_records'] = $data_array[1] : $fields['file_format_error'] = "Value for # of recs failed pattern match."; 
        break;
        
        case 10:
        //  [10]=> string(19) "battery level: 02bd" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $fields['file_format_error'] = "Expected colon to split battery level string into array.";      
        strpos($data_array[0], 'battery') !== false ? '' : $fields['file_format_error'] = "Expected 'battery' label.";
        preg_match('/^[0-9a-f]{4}/', $data_array[1]) ? $fields['battery_level'] = $data_array[1] : $fields['file_format_error'] = "Value for battery level failed pattern match.";
        break;
        
        case 11:
        //  [11]=> string(0) ""
        if ($value == '') {
          $record_set = array_slice($array,12);
          
          if (count($record_set) > 4) { // at least one record
          
            $parsed_records = $this->_parseRecords($record_set);
            
            // check if file_format_error had been set in any of the records
            foreach ($parsed_records as $k => $v) {  
                if (is_array($v)) {
                  foreach ($v as $a => $b) {  
                    ($a == 'file_format_error') ? $fields['file_format_error'] = $b : '';
                  }
                }
            }
          } 
          else {
            $fields['file_format_error'] = "Record set contains no records.";
          }     
        } 
        else {
          $fields['file_format_error'] = "Line of report expected blank. See 'case 11:' of of parseSensorReports().";
        }
      } 
    }
    
    if (isset($parsed_records)) {
       return array_merge($fields,$parsed_records);
    }
    else {
      return $fields;
    }
  }

  function _parseRecords($record_set)
  {
    $fields = array();
    
    /* test record header */
    $test_array = explode("#", $record_set[0]);
    $test_value = $test_array[0];
    if ($test_value != 'rec' ) {
      $fields['file_format_error'] = "Expected column labels for record set.";  
    }       
  
    foreach ($record_set as $label => $value) {
          
      if ($label > 0 and $value != '') {
        
        $report_array = explode(",", $value); 
        $report_array = array_map('trim', $report_array);
             
       
         if (count($report_array) == 17 ) {
           
          preg_match('/^[0-9a-f]{4}/', $report_array[0]) ? $fields['record_num'] = $report_array[0] : $fields['file_format_error'] = "Record value for record num failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[1]) ? $fields['temperature'] = $report_array[1] : $fields['file_format_error'] = "Record value for temperature failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[2]) ? $fields['x'] = $report_array[2] : $fields['file_format_error'] = "Record value for X failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[3]) ? $fields['y'] = $report_array[3] : $fields['file_format_error'] = "Record value for Y failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[4]) ? $fields['z'] = $report_array[4] : $fields['file_format_error'] = "Record value for Z failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[5]) ? $fields['cnt'] = $report_array[5] : $fields['file_format_error'] = "Record value for CNT failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[6]) ? $fields['max'] = $report_array[6] : $fields['file_format_error'] = "Record value for MAX failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[7]) ? $fields['bin_a'] = $report_array[7] : $fields['file_format_error'] = "Record value for BIN A failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[8]) ? $fields['bin_b'] = $report_array[8] : $fields['file_format_error'] = "Record value for BIN B failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[9]) ? $fields['bin_c'] = $report_array[9] : $fields['file_format_error'] = "Record value for BIN C failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[10]) ? $fields['bin_d'] = $report_array[10] : $fields['file_format_error'] = "Record value for BIN D failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[11]) ? $fields['bin_e'] = $report_array[11] : $fields['file_format_error'] = "Record value for BIN E failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[12]) ? $fields['bin_f'] = $report_array[12] : $fields['file_format_error'] = "Record value for BIN F failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[13]) ? $fields['bin_g'] = $report_array[13] : $fields['file_format_error'] = "Record value for BIN G failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[14]) ? $fields['bin_h'] = $report_array[14] : $fields['file_format_error'] = "Record value for BIN H failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[15]) ? $fields['bin_i'] = $report_array[15] : $fields['file_format_error'] = "Record value for BIN I failed pattern match.";
          preg_match('/^[0-9a-f]{4}/', $report_array[16]) ? $fields['bin_j'] = $report_array[16] : $fields['file_format_error'] = "Record value for BIN J failed pattern match.";
         
          $reports[$label] = $fields;       
        } 
        else {
          
          // store as a comm message if not at end of file
          $test_string = substr($report_array[0], 0, 5);
          if ($test_string != '--end' and $test_string != 'turtl') {
            $reports['devicemsg-'.$label] = $report_array[0];
            //$this->logmodel->writeToApplicationLog("You've got a message!\n".$report_array[0]);
          }
        }        
      }
    }
    //echo '<pre>';print_r($fields);  
    return $reports;
  }

  
  // INSERTS
  
  function insertNest($data_fields)
  {
    $nest['nest_date'] = $data_fields['event_date'];
    $nest['sensor_id'] = $data_fields['sensor_id'];
    $nest['comm_id']   = $data_fields['comm_id'];
    $nest['latitude']  = $data_fields['latitude'];
    $nest['longitude'] = $data_fields['longitude'];
    $nest['active']    = 1;
  
    return $this->_doInsertAutoId('NESTS', $nest);		
  }
  
  function insertEvent_SensorRegistration($data_fields)
  {
    $event['event_type']     = $data_fields['event_type'];
    $event['event_datetime'] = $data_fields['event_datetime'];
    $event['nest_id']        = $data_fields['nest_id'];
    $event['sensor_id']      = $data_fields['sensor_id'];
    $event['comm_id']        = $data_fields['comm_id'];
    $event['battery_level']  = $data_fields['battery_level'];
  
    return $this->_doInsertAutoId('EVENTS', $event);
  }
  
  function insertSensor($data_fields)
  {
    $sensor['sensor_id']       = $data_fields['sensor_id'];
    $sensor['nest_id']         = $data_fields['nest_id'];
    $sensor['sensor_firstuse'] = $data_fields['event_datetime'];
    $sensor['sensor_lastuse']  = $data_fields['event_datetime'];
    $sensor['sensor_inuse']    = 1;
      
    return $this->_doInsert('SENSORS', $sensor);		
  }
  
  function insertComm($data_fields)
  {
    $commtype = ($data_fields['event_type'] == 'report') ? 'tower' : 'hand-held';
    
    $comm['comm_id']       = $data_fields['comm_id'];
    $comm['nest_id']       = $data_fields['nest_id'];
    $comm['comm_type']     = $commtype;
    $comm['comm_firstuse'] = $data_fields['event_datetime'];
    $comm['comm_lastuse']  = $data_fields['event_datetime'];
    $comm['comm_inuse']    = 1;
      
    return $this->_doInsert('COMMUNICATORS', $comm);		
  }
  
  function _doInsert($table, $values)
  {
    $this->db->insert($table, $values);
  	if ($this->db->affected_rows() == 1) {
  		return true;
  	} else {
  		return false;
  	}	
  }
  
  function _doInsertAutoId($table, $values)
  {
    $this->db->insert($table, $values);
  	if ($this->db->affected_rows() == 1) {
  		return $this->db->insert_id();
  	} else {
  		return false;
  	}			
  }

  // UPDATES
   
  function updateNest($data_fields)
  {    
    $nestid = $data_fields['nest_id'];
    
    $nest['comm_id']   = $data_fields['comm_id'];
    $nest['latitude']  = $data_fields['latitude'];
    $nest['longitude'] = $data_fields['longitude'];
    $nest['active']    = 1;
    
    $this->db->where('nest_id', $nestid);
    $this->db->update('NESTS', $nest);
    
  	if ($this->db->affected_rows() == 1) {
  		return true; 
  	} else { 	
  		return false;
  	}			
  }
       
  function updateSensor($data_fields)
  {
    $sensorid = $data_fields['sensor_id'];
    
    $sensor['nest_id']        = $data_fields['nest_id'];
    $sensor['sensor_lastuse'] = $data_fields['event_datetime'];
    $sensor['sensor_inuse']   = 1;
    
    $this->db->where('sensor_id', $sensorid);
    $this->db->update('SENSORS', $sensor);
  	if ($this->db->affected_rows() == 1) {
  		 return true; 
  	} else {
  		return false;
  	}			
  }
       
  function updateComm($data_fields)
  {
    $commid = $data_fields['comm_id'];

    $comm['nest_id']      = $data_fields['nest_id'];
    $comm['comm_lastuse'] = $data_fields['event_datetime'];
    $comm['comm_inuse']   = 1;

    $this->db->where('comm_id', $commid);
    $this->db->update('COMMUNICATORS', $comm);
  	if ($this->db->affected_rows() == 1) {
  		 return true; 
  	} else {
  		return false;
  	}			
  }
         
  function deActivateNestByNestID($nestid)
  {
    $nest['active'] = 0;
    
    $this->db->where('nest_id', $nestid);
    $this->db->update('NESTS', $nest);
    
  	if ($this->db->affected_rows() == 1) {
  		 return true; 
  	} else {
  		return false;
  	}			
  }
  
  function eventExists($data_fields)
  {
    $this->db->where('sensor_id', $data_fields['sensor_id']);
    $this->db->where('event_datetime', $data_fields['event_datetime']);
    $query = $this->db->get('EVENTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->event_id;
    } else {   
      return false;
    }
  }
  
  function activeSensorExistsInNests($sensorid)
  {
    $this->db->where('sensor_id', $sensorid);
    $this->db->where('active', 1);
    $query = $this->db->get('NESTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->nest_id;
    } else {   
      return false;
    }
  }
  
  function nestExists($data_fields)
  {
    $this->db->where('sensor_id', $data_fields['sensor_id']);
    $this->db->where('nest_date', $data_fields['event_date']);
    $query = $this->db->get('NESTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->nest_id;
    } else {   
      return false;
    }
  }
  
  function sensorExists($data_fields)
  {
    $table = 'SENSORS';
    $label = 'sensor_id';
    $id    = $data_fields['sensor_id'];

    return $this->_recordExists($table,$label,$id);
  }
  
  function commExists($data_fields)
  {
    $table = 'COMMUNICATORS';
    $label = 'comm_id';
    $id    = $data_fields['comm_id'];

    return $this->_recordExists($table,$label,$id);
  }
  
  function _recordExists($table,$label,$id)
  {
    $this->db->where($label ,$id);
    $query = $this->db->get($table);
    
    $result = $query->row();
    if (count($result) ) {
      return $result->{$label};
    } else {   
      return false;
    }
  }

  function moveMalformedLogFile($data_fields)
  {
    $targetFileName = basename($data_fields['file_name']);
    $sourceFile     = $data_fields['file_name'];

    $targetDirPath  = $this->config->item('reports_malformed_dir');  
    $targetFile     = $targetDirPath.'/'.$targetFileName;
    
  	if(file_exists($sourceFile)){
  		rename($sourceFile,$targetFile);
  	}
  }
  
  function _setErrorMsg($type, &$fields)
  {
    switch($type)
    {
      case 'date':
        $fields['file_format_error'] = "Malformed line of data for 'Date/Time'.";
        break;
        
      case 'sensor':
        $fields['file_format_error'] = "Malformed line of data for 'Sensor ID'.";
        break;
        
      case 'comm':
        $fields['file_format_error'] = "Malformed line of data for 'Communicator ID'.";
        break;
        
      case 'gps':
        $fields['file_format_error'] = "Malformed line of data for 'GPS Location'.";
        break;
        
      case 'battery':
        $fields['file_format_error'] = "Malformed line of data for 'Battery Level'.";
        break;
        
      default:
        $fields['file_format_error'] = "No error message provided.";
        break;
    }
  }
  
}		
/* EOF */