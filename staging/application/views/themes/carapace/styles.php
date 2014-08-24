<?php /* styles for all pages  */ ?>
<link rel="stylesheet" href="<?=$this->config->item('stylesheet_path')?>main.css" type="text/css" />

<?php /* styles for specific pages */
 	if(isset($additionalStyles)){
		foreach ($additionalStyles as $style){
			echo $style;
		}
	}
/*<!--[if lt IE 7]>
<link type='text/css' href='' rel='stylesheet' media='screen' />
<![endif]-->*/
?>

