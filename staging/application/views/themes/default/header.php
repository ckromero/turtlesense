<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->

<head>

	<title><?=isset($pagetitle) ? $pagetitle : 'Page Title'; ?></title>
	
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="description" content="">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	
	<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,300,600' rel='stylesheet' type='text/css'>
	<?php $this->load->view($this->config->item('theme_path').'styles');?>	
	<?php /* <script src="<?=$this->config->item('javascript_path')?>vendor/modernizr-2.6.2-respond-1.1.0.min.js"></script>*/?>

</head>

<body>
<!--[if lt IE 7]>
    <p class="browsehappy">You are using an <strong>outdated</strong> browser. You can <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience on the Web!</p>
<![endif]-->

<p>header</p>