<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

$config['parser_logs_dir']    = realpath(BASEPATH . '../../logs/parser');
$config['reports_dir']        = realpath(BASEPATH . '../../reports');

$config['processed_reports_dir']  = realpath(BASEPATH . '../../processed');
$config['malformed_reports_dir']  = realpath(BASEPATH . '../../malformed');


/*
$config['logs_directory']  	        = realpath(BASEPATH . '../logs');
$config['logs_directory_payflow']  	= realpath(BASEPATH . '../logs/payflow');
$config['upload_directory']         = realpath(BASEPATH . '../../uploads');
$config['tmp_directory']  	        = realpath(BASEPATH . '../../tmp');
*/


/* EOF */
