<?php
/**
 *@ 数据库同步脚本
 **/
	require_once dirname(__FILE__).'/inc/inc.php';
	$nums = $_SERVER['argc'];
	for( $i=1;$i<$nums;$i+=2 ){
		switch( $_SERVER['argv'][$i] ){
			/*case '-t':
				$data['table'] = $_SERVER['argv'][$i+1];break;
			case '-d':
				$info = $_SERVER['argv'][$i+1];
				$data['data'] = unserialize($info);
				break;
			case '-w':
				$where = $_SERVER['argv'][$i+1];
				$data['where'] = unserialize($where);
				break;
			case '-o':
				$data['opt'] = $_SERVER['argv'][$i+1];break;
			case '-f':
				$data['tag'] = $_SERVER['argv'][$i+1];break;*/
			case '-s':
				$data = unserialize( $_SERVER['argv'][$i+1] );break;
		}
	}
	if( empty( $data ) ){
		exit('参数错误');
	}
	$sync = new Sync( $data );
	$sync->exec();

?>