<?php /* scripts for all pages */ ?>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="<?=$this->config->item('javascript_path')?>vendor/jquery-1.11.0.min.js"><\/script>')</script>
<script src="<?=$this->config->item('javascript_path')?>main.js"></script>

<?php /* scripts for specific pages */
 	if(isset($headerScripts)){
		foreach ($headerScripts as $script){
			echo $script;
		}
	}
/*<!--[if IE]><script type="text/javascript" src=""></script><![endif]-->*/
?>

