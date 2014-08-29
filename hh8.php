<?php
	function format( $file ){
		$file = preg_replace( '/\/\/.*?\\s/','', $file );    			//去除//注释
		$file = preg_replace( '/[\f\n\r\t\v]/','', $file );			//去除文件中的空白与换行
		$file = preg_replace('/(\/\*).*?\//', '', $file);			//去除文件中的/*..*/格式的注释
		$file = str_replace(array('<?php','?>','<?'),array('','',''),$file);	//去除文件中的php格式
		return $file;
	}

	function hzyEncode($file){
		$file = format($file);
		$ret = '';
		for( $i=0;$i<strlen($file);$i++ ){
			echo $file[$i].'_'.ord($file[$i]).'_'.base_convert(ord($file[$i]),10,2);
			echo "\n";
			$ret .= base_convert(ord($file[$i]),10,2);
		}
		return $ret;
	}

	function hzyDecode($str){
		$ret = '';
		for( $i=0;$i<strlen($str);$i+=2 ){
			$ret .= chr(base_convert($str[$i].$str[$i+1], 2, 10));
		}
		return $ret;
	}
	$file = file_get_contents('test.php');
	file_put_contents('test_new.php',hzyEncode($file));
	echo $file = file_get_contents('test_new.php');
	$file = hzyDecode($file);
	echo($file);
?>