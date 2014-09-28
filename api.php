<?php
	error_reporting(0);
	global $log,$tag,$version,$channel,$serverId;
	require dirname(__FILE__).'/inc/inc.php';
	C('test',false);
	C('start', gettimeofday( true ));
	$mid = getReq('cmd',7003);
	$key = getReq('k','6d3126a871959600866d3bc451fac21f');
	$version = $ver = getReq('ver','1.0.0');
	$channel = getReq('ch');
	$serverId = getReq('sid');
	if( empty($mid) || empty($key) ){ //mid和key必须存在
		ret('cmd or key not null!',-1);
	}
	$mod = new Public_Modinit($mid,$key,$ver);
	$tag = $mod->name();
	$log = new Logger($mod->tag());
	$bin = file_get_contents('php://input');
	$input = msgpack_unpack( $bin );
	if( '' == getReq( 'cmd' ) )
		$input = json_decode('{"t":1,"n":1}',true);
	if( C('test') ){
		$tag .= '=DB模式=';
	}else{
		$tag .= '=cache模式=';
	}
	$log->i( '接口调用开始['.($ver?$ver:0).'] - '.$tag.' IP:'.long2ip(getIp()) );
	$log->i(json_encode($input));
	$log->i( json_encode($_REQUEST) );
	$filepath = $mod->path();
	unset($mod);
	if( is_file( BOOT.$filepath ) ){
		require BOOT.$filepath;
	}else{
		ret('Module file not exist!',-1);
	}
