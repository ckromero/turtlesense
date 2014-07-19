<?php
/* out of play

isset($events['file_format_error']) ? $events['file_format_error'].'<br><br>' : '';

if (isset($events)) {
  
  foreach ($events as $data_fields) {
    foreach ($data_fields as $k => $v) {
    
      if (is_array($v)) {
        
        echo('<pre>');
        print_r($v);
        //echo('</pre>');
        
          
      } 
      else {
        echo $k.': <span style="color:blue;">'.$v.'</span><br>';
      }  
      
    } 
    echo('<br>');
  }
  
}
*/
