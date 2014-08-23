<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Parse extends CI_Model {

	function __construct()
	{
		parent::__construct();
    $this->load->model('log', 'logmodel');	
	}			
	
  function parse_NestRegistration($logEntry_lines = array())
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
        $fields['nest_date'] = $match[0];
        break;
        
        case 1:
        //  [1]=> string(18) "Sensor ID#: AA0003"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('sensor',$fields);      
        strpos($data_array[0], 'Sensor ID') !== false ? '' : $this->_setErrorMsg('sensor',$fields);
        preg_match('/^\w{2}\d{4}/', $data_array[1]) ? $fields['sensor_id'] = $data_array[1] : $this->_setErrorMsg('sensor',$fields);
        break;
        
        case 2:
        //  [2]=> string(39) "Registration Communicator ID#: H-AA0005"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('comm',$fields);      
        strpos($data_array[0], 'Communicator') !== false ? '' : $fields['file_format_error'] = $this->_setErrorMsg('comm',$fields);
        preg_match('/^\w-\w{2}\d{4}/', $data_array[1]) ? $fields['comm_id'] = $data_array[1] : $this->_setErrorMsg('comm',$fields);
        preg_match('/^\w-/', $data_array[1], $match);
        ($match[0] == 'H-') ? $fields['comm_type'] = 'handheld' : $this->_setErrorMsg('commtype',$fields);
        break;
        
        case 3:
        //  [3]=> string(42) "Nest GPS Location: 3514.7907N, 07531.4702W" 
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
  
  function parse_NestReport($array = array())
  {
    $fields = array();


//echo '<pre>';print_r($array);
    
    foreach ($array as $label => $value) {
      
      switch ($label) {
        
        case 0:
        //  [0]=> string(38) "Report: 2014-06-30_AA0003_r-005-01.txt"
        $fields['event_type'] = 'nest report';
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('report',$fields);      
        strpos($data_array[0], 'Report') !== false ? '' : $this->_setErrorMsg('report',$fields);
        preg_match('/^\d{4}-\d{2}-\d{2}_\w{2}\d{4}_r-\d{2,3}-\d{2,3}/', $data_array[1], $match) ? $fields['report_filename_id'] = $match[0] : $this->_setErrorMsg('report',$fields);
        break;

        case 1:
        //  [1]=> string(18) "Sensor ID#: AA0003"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('sensor',$fields);      
        strpos($data_array[0], 'Sensor ID') !== false ? '' : $this->_setErrorMsg('sensor',$fields);
        preg_match('/^\w{2}\d{4}/', $data_array[1]) ? $fields['sensor_id'] = $data_array[1] : $this->_setErrorMsg('sensor',$fields);
        break;

        case 2:
        //  [2]=> string(21) "Installed: 2014-06-30"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('installed',$fields); 
        strpos($data_array[0], 'Installed') !== false ? '' : $this->_setErrorMsg('installed',$fields);
        preg_match('/^\d{4}-\d{2}-\d{2}/', $data_array[1]) ? $fields['nest_date'] = $data_array[1] : $this->_setErrorMsg('installed',$fields);
        break;
        
        case 3:
        //  [3]=> string(18) "Comm ID#: C-AA0002"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('comm',$fields);      
        strpos($data_array[0], 'Comm ID') !== false ? '' : $this->_setErrorMsg('comm',$fields);
        preg_match('/^\w{1}\-\w{2}\d{4}/', $data_array[1]) ? $fields['comm_id'] = $data_array[1] : $this->_setErrorMsg('comm',$fields); 
        preg_match('/^\w-/', $data_array[1], $match);
        ($match[0] == 'C-') ? $fields['comm_type'] = 'tower' : $this->_setErrorMsg('commtype',$fields);
        break;
        
        case 4:
        //  [4]=> string(30) "Days active & report #: 005-01" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('daysactive',$fields);
        strpos($data_array[0], 'Days active') !== false ? '' : $this->_setErrorMsg('daysactive',$fields);
        strpos($data_array[1], '-') !== false ? $data_array = explode("-", $data_array[1]) : $this->_setErrorMsg('daysactive',$fields);      
        preg_match('/^\d{2,3}/', $data_array[0]) ? $fields['days_active'] = $data_array[0] : $this->_setErrorMsg('daysactive',$fields);
        preg_match('/^\d{2,3}/', $data_array[1]) ? $fields['report_num'] = $data_array[1] : $this->_setErrorMsg('daysactive',$fields);
        break;

        case 5:
        //  [5]=> string(37) "Nest location: 3514.7907N,07531.4702W"
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('gps',$fields);              
        strpos($data_array[0], 'Nest location') !== false ? '' : $this->_setErrorMsg('gps',$fields);       
        strpos($data_array[1], ',') !== false ? $data_array = explode(',', $data_array[1]) : $this->_setErrorMsg('gps',$fields);             
        preg_match('/^\d{4,5}.\d{4}\w/', $data_array[0]) ? $fields['latitude']  = $data_array[0] : $this->_setErrorMsg('gps',$fields);
        preg_match('/^\d{4,5}.\d{4}\w/', $data_array[1]) ? $fields['longitude'] = $data_array[1] : $this->_setErrorMsg('gps',$fields);
        break;
        
        case 6:
        //  [6]=> string(36) "Start date/time: 2014/07/03,23:57:32"  
        $string = str_replace(': ', '::', $value);
        strpos($string, '::') !== false ? $data_array = explode("::", $string) : $this->_setErrorMsg('startdate',$fields);     
        strpos($data_array[0], 'Start date/time') !== false ? '' : $this->_setErrorMsg('startdate',$fields);
        $data_array[1] = str_replace(',', '', $data_array[1]); // remove comma from date/time string for php
        preg_match('/^\d{4}\/\d{2}\/\d{2}\d{2}\:\d{2}\:\d{2}/', $data_array[1]) ? $fields['start_datetime'] = $data_array[1] : $this->_setErrorMsg('startdate',$fields);
        break;
        
        case 7:
        //  [7]=> string(37) "Report date/time: 2014/07/04,01:57:35"
        $string = str_replace(': ', '::', $value);
        strpos($string, '::') !== false ? $data_array = explode("::", $string) : $this->_setErrorMsg('reportdate',$fields);      
        strpos($data_array[0], 'Report date/time') !== false ? '' : $this->_setErrorMsg('reportdate',$fields); 
        $data_array[1] = str_replace(',', ' ', $data_array[1]); // remove comma from date/time string for php
        preg_match('/^\d{4}\/\d{2}\/\d{2}\s\d{2}\:\d{2}\:\d{2}/', $data_array[1]) ? $fields['event_datetime'] = $data_array[1] : $this->_setErrorMsg('reportdate',$fields); 
        break;

        case 8:
        //  [8]=> string(18) "Secs per rec: 0168" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('secsperrec',$fields);              
        strpos($data_array[0], 'Secs per rec') !== false ? '' : $this->_setErrorMsg('secsperrec',$fields); 
        preg_match('/^[0-9A-F]{4}/', $data_array[1]) ? $fields['secs_per_record'] = $data_array[1] : $this->_setErrorMsg('secsperrec',$fields);  
        break;
        
        case 9:
        //  [9]=> string(15) "# of recs: 0014"  
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('numrecs',$fields);               
        strpos($data_array[0], '# of recs') !== false ? '' : $this->_setErrorMsg('numrecs',$fields); 
        preg_match('/^[0-9A-F]{4}/', $data_array[1]) ? $fields['num_records'] = $data_array[1] : $this->_setErrorMsg('numrecs',$fields); 
        break;
        
        case 10:
        //  [10]=> string(19) "Battery level: 02BD" 
        strpos($value, ':') !== false ? $data_array = explode(": ", $value) : $this->_setErrorMsg('battery',$fields);     
        strpos($data_array[0], 'Battery') !== false ? '' : $this->_setErrorMsg('battery',$fields);
        preg_match('/^[0-9A-F]{4}/', $data_array[1]) ? $fields['battery_level'] = $data_array[1] : $this->_setErrorMsg('battery',$fields);
        break;
      } 
    }
    
    return $fields;
//echo '<pre>';print_r($fields);exit;
    
  }

  function parse_NestRecords($record_set)
  {
    $fields = array();

    /* record header */
    $test_array = explode("#", $record_set[0]);
    if ($test_array[0] != 'Rec' ) {
      $fields['file_format_error'] = "Expected column headers for record set.";  
    }       
  
    foreach ($record_set as $label => $value) {
          
      if ($label > 0 and $value != '') {
        
        $report_array = explode(",", $value); 
        $report_array = array_map('trim', $report_array);
       
         if (count($report_array) == 17 ) {
           
          preg_match('/^[0-9A-F]{4}/', $report_array[0]) ? $fields['record_num'] = $report_array[0] : $fields['file_format_error'] = "Record value for record num failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[1]) ? $fields['temperature'] = $report_array[1] : $fields['file_format_error'] = "Record value for temperature failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[2]) ? $fields['x'] = $report_array[2] : $fields['file_format_error'] = "Record value for X failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[3]) ? $fields['y'] = $report_array[3] : $fields['file_format_error'] = "Record value for Y failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[4]) ? $fields['z'] = $report_array[4] : $fields['file_format_error'] = "Record value for Z failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[5]) ? $fields['cnt'] = $report_array[5] : $fields['file_format_error'] = "Record value for CNT failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[6]) ? $fields['max'] = $report_array[6] : $fields['file_format_error'] = "Record value for MAX failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[7]) ? $fields['bin_a'] = $report_array[7] : $fields['file_format_error'] = "Record value for BIN A failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[8]) ? $fields['bin_b'] = $report_array[8] : $fields['file_format_error'] = "Record value for BIN B failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[9]) ? $fields['bin_c'] = $report_array[9] : $fields['file_format_error'] = "Record value for BIN C failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[10]) ? $fields['bin_d'] = $report_array[10] : $fields['file_format_error'] = "Record value for BIN D failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[11]) ? $fields['bin_e'] = $report_array[11] : $fields['file_format_error'] = "Record value for BIN E failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[12]) ? $fields['bin_f'] = $report_array[12] : $fields['file_format_error'] = "Record value for BIN F failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[13]) ? $fields['bin_g'] = $report_array[13] : $fields['file_format_error'] = "Record value for BIN G failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[14]) ? $fields['bin_h'] = $report_array[14] : $fields['file_format_error'] = "Record value for BIN H failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[15]) ? $fields['bin_i'] = $report_array[15] : $fields['file_format_error'] = "Record value for BIN I failed pattern match.";
          preg_match('/^[0-9A-F]{4}/', $report_array[16]) ? $fields['bin_j'] = $report_array[16] : $fields['file_format_error'] = "Record value for BIN J failed pattern match.";
         
          $reports[$label] = $fields;       
        } 
        else {
          
          // store row as a comm message if not at end of file
          $test_string = substr($report_array[0], 0, 5);
          if ($test_string != '--end' and $test_string != 'turtl') {
            $reports['devicemsg-'.$label] = $report_array[0];
          }
        }        
      }
    }
     
    $reports['event_type'] = "nest record";
    //return $reports;
//echo '<pre>';print_r($reports);exit;


  }


  // GETS
 
 	function getFiles() 
	{	
	  $files = get_filenames($this->config->item('reports_to_parse_dir'), TRUE);	  
		return $files;
	}
			
	function getNestById($nestid) 
	{	
		$this->db->where('nest_id', $nestid);		
		$query = $this->db->get('NESTS');
		$result = $query->row();		
		return $result;
	}
			
 
  // INSERTS
  
  function insertNest($data_fields)
  {
    $nest['nest_date'] = $data_fields['nest_date'];
    $nest['sensor_id'] = $data_fields['sensor_id'];
    $nest['comm_id']   = $data_fields['comm_id'];
    $nest['latitude']  = $data_fields['latitude'];
    $nest['longitude'] = $data_fields['longitude'];
    $nest['active']    = 1;
  
    return $this->_doInsertAutoId('NESTS', $nest);		
  }
  
  function insertReport($data_fields)
  {
    $report['report_filename_id'] = $data_fields['report_filename_id'];
    $report['sensor_id'] = $data_fields['sensor_id'];
    $report['comm_id']   = $data_fields['comm_id'];
    $report['latitude']  = $data_fields['latitude'];
    $report['longitude'] = $data_fields['longitude'];
    $report['active']    = 1;
  
    return $this->_doInsertAutoId('REPORTS', $report);		
  }
  
  function _insertEvent($data_fields)
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
    $comm['comm_id']       = $data_fields['comm_id'];
    $comm['comm_type']     = $data_fields['comm_type'];
    $comm['nest_id']       = $data_fields['nest_id'];
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
   
  function _updateNest($data_fields)
  {    
    $nestid = $data_fields['nest_id'];
    
    $nest = $this->getNestById($nestid);
    
    $incoming_nest['comm_id']   = $data_fields['comm_id'];
    $incoming_nest['latitude']  = $data_fields['latitude'];
    $incoming_nest['longitude'] = $data_fields['longitude'];
    $incoming_nest['nest_date'] = $data_fields['nest_date'];
    
    // if nest_date of incoming is greater than recorded
    if ($incoming_nest['nest_date'] > $nest->nest_date) {
      $incoming_nest['active']    = 1;
    }
        
    $this->db->where('nest_id', $nestid);
    $this->db->update('NESTS', $nest);
    
  	if ($this->db->affected_rows() == 1) {
  		return TRUE; 
  	} 	
    return FALSE; 			
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
  
  
 
  // INQUERIES 
  
  function _nestExists($data_fields)
  {
    $this->db->where('sensor_id', $data_fields['sensor_id']);
    $this->db->where('nest_date', $data_fields['nest_date']);
    $query = $this->db->get('NESTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->nest_id;
    }    
    return FALSE;   
  }

  function _reportExists($data_fields)
  {
    $this->db->where('report_filename_id');
    $query = $this->db->get('REPORTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->report_id;
    }    
    return FALSE;
  }
  
  function _sensorExists($data_fields)
  {
    $table = 'SENSORS';
    $label = 'sensor_id';
    $id    = $data_fields['sensor_id'];

    return $this->_recordExists($table,$label,$id);
  }
  
  function _commExists($data_fields)
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
    }   
    return FALSE;
  }

  function eventExists($data_fields)
  {
    $this->db->where('sensor_id', $data_fields['sensor_id']);
    $this->db->where('event_datetime', $data_fields['event_datetime']);
    $this->db->where('event_type', $data_fields['event_type']);    
    $query = $this->db->get('EVENTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->event_id;
    }    
    return FALSE;
  }
  
  function _activeSensorExistsInNests($sensorid)
  {
    $this->db->where('sensor_id', $sensorid);
    $this->db->where('active', 1);
    $query = $this->db->get('NESTS');
    
    $result = $query->row();
    if (count($result) ) {
      return $result->nest_id;
    } 
    return FALSE;
  }

 
  // HELPERS
    
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
        
      case 'commtype':
        $fields['file_format_error'] = "Unexpected prefix in communicator Id.";
        break;

      case 'gps':
        $fields['file_format_error'] = "Malformed line of data for 'GPS Location'.";
        break;
        
      case 'battery':
        $fields['file_format_error'] = "Malformed line of data for 'Battery Level'.";
        break;

      case 'report':
        $fields['file_format_error'] = "Malformed line of data for 'Report: filename'.";
        break;

      case 'installed':
        $fields['file_format_error'] = "Malformed line of data for 'Installed'.";
        break;

      case 'daysactive':
        $fields['file_format_error'] = "Malformed line of data for 'Days active'.";
        break;

      case 'startdate':
        $fields['file_format_error'] = "Malformed line of data for 'Start date'.";
        break;
        
      case 'reportdate':
        $fields['file_format_error'] = "Malformed line of data for 'Report date'.";
        break;
        
      case 'secsperrec':
        $fields['file_format_error'] = "Malformed line of data for 'Secs per rec'.";
        break;
        
      case 'numrecs':
        $fields['file_format_error'] = "Malformed line of data for '# of recs'.";
        break;
        
      default:
        $fields['file_format_error'] = "No error message provided.";
        break;
    }
  }
 

 // DATABASE CONTROLLERS
  
  function dba_nestRegistration($data_fields)
  {
    $nest_exists = $this->_nestExists($data_fields);       
    if ($nest_exists) {
      
      $data_fields['nest_id'] = $nest_exists;
      
    	$this->_updateNest($data_fields);
      $this->_insertEvent($data_fields);  
      $this->_updateSensor($data_fields);

      $comm_exists = $this->_commExists($data_fields);            	
    	if ($comm_exists) {   
    		$this->_updateComm($data_fields);    
    	} 
    	else {
    		$this->_insertComm($data_fields);     
      }
    } 
    else { //nest does not exist
      
      $sensorid = $data_fields['sensor_id'];
    	$activeSensorExistsInNests = $this->_activeSensorExistsInNests($sensorid);   
    	
    	if ($activeSensorExistsInNests) {   	
        $nestid = $activeSensorExistsInNests;
    		$this->deActivateNestByNestID($nestid);
      }
      
    	$data_fields['nest_id'] = $this->insertNest($data_fields);
    	$this->_insertEvent($data_fields);
    
      $sensor_exists = $this->_sensorExists($data_fields);
      $sensor_exists ? $this->updateSensor($data_fields) : $this->insertSensor($data_fields);

      $comm_exists = $this->_commExists($data_fields);
      $comm_exists ? $this->updateComm($data_fields) : $this->insertComm($data_fields);     
    }
  }
 
  
  function dba_nestReport($data_fields)
  {
    $report_exists = $this->_reportExists($data_fields);   
        
    if ($report_exists) {
      
      $data_fields['report_id'] = $report_exists;
    } 
    else { //report does not exist
            
  // !IM HERE - Write db logic for reports and then for records
    	$data_fields['report_id'] = $this->insertReport($data_fields);
    	$this->_insertEvent($data_fields); // How is NestRegistration diff from Event_NestReport???
    
      $sensor_exists = $this->_sensorExists($data_fields);
      $sensor_exists ? $this->updateSensor($data_fields) : $this->insertSensor($data_fields);

      $comm_exists = $this->_commExists($data_fields);
      $comm_exists ? $this->updateComm($data_fields) : $this->insertComm($data_fields);     
    }
  }
 
}		
/* EOF */