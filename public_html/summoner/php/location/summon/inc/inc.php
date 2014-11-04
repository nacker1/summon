<?php
	define('BOOT',dirname(__DIR__));
	date_default_timezone_set('Asia/Shanghai');
	require BOOT.'/inc/function.php';
	require BOOT.'/inc/config.php';

	function myAutoload($classname){
		$class = explode('_',$classname);
		if(is_array($class) && isset($class[1]) && is_file(BOOT.'/lib/'.$class[0].'/'.$class[1].'.class.php')){
			require(BOOT.'/lib/'.$class[0].'/'.$class[1].'.class.php');
		}else if( is_file(BOOT.'/lib/Public/'.$classname.'.class.php') ){
			require(BOOT.'/lib/Public/'.$classname.'.class.php');
		}else{
			#ret($classname.' Class not find!',-1);
		}
	}

	spl_autoload_register( 'myAutoload');
?>