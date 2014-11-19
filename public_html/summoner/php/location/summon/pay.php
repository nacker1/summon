<?php
/**
 *@ 充值统一入口
 **/
	error_reporting(0);
	global $log,$tag,$version,$channel;
	require dirname(__FILE__).'/inc/inc.php';
	C('test',false);
	C('start', gettimeofday( true ));
	$mid = getReq('cmd',1000);
	$key = getReq('k','ed1bf251c05aee111f2ea3e89a2b5d27');
	$version = $ver = getReq('ver','1.0.0');
	$channel = getReq('ch');
	if( empty($mid) || empty($key) ){ //mid和key必须存在
		ret('cmd or key not null!',-1);
	}
	$mod = new Public_Modinit($mid,$key,$ver);
	$tag = $mod->name();
	$log = new Logger($mod->tag());
	$bin = file_get_contents('php://input');
	
	$log->f( '充值调用开始['.($ver?$ver:0).'] - '.$tag.' IP:'.long2ip(getIp()) );
	$log->d(json_encode($input));
	$log->d( json_encode($_REQUEST) );
	$filepath = $mod->path();
	unset($mod);
	if( is_file( BOOT.$filepath ) ){
		require BOOT.$filepath;
	}else{
		ret('Module file not exist!',-1);
	}
?>