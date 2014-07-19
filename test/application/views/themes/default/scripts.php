<?php /* scripts for all pages */ ?>
<script src="<?=$this->config->item('javascript_path')?>main-min.js"></script>

<?php /* scripts for specific pages */
 	if(isset($headerScripts)){
		foreach ($headerScripts as $script){
			echo $script;
		}
	}
/*<!--[if IE]><script type="text/javascript" src=""></script><![endif]-->*/
?>

