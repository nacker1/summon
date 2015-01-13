<?php
/**
 *@ 数据库同步脚本
 **/
	error_reporting(0);
	require_once dirname(__FILE__).'/inc/inc.php';
/*  抛出SQL模式使用  无需要启动监听进程
	$nums = $_SERVER['argc'];
	for( $i=1;$i<$nums;$i+=2 ){
		switch( $_SERVER['argv'][$i] ){
			case '-t':
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
				$data['opt'] = $_SERVER['argv'][$i+1];
				if( empty( $data['opt'] ) ) unset( $data['opt'] );
				break;
			case '-f':
				$data['tag'] = $_SERVER['argv'][$i+1];
				if( empty( $data['opt'] ) ) unset( $data['tag'] );
				break;
		}
	}
	if( empty( $data['table'] ) ){
		exit('参数错误');
	}
	$sync = new Sync( $data );
	$sync->exec();
*/

#================   开启监听进程，监听20030端口redis   ==============================
	$sync_redis = Redis_Redis::init( 'sync_db' );
	while(1){
		$data = $sync_redis->lpop( 'sync_db_select' );
		if( empty( $data ) ){
			sleep(3);  #如果没数据需要同步休息3秒钟
		}
		$data = json_decode( $data, true );
		if( is_array( $data ) ){
			$sync = new Sync( $data );
			$sync->exec();
		}
	}
?>