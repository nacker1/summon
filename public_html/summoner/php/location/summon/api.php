<?php
	error_reporting(0);
	global $log,$tag,$version,$channel,$serverId;
	require dirname(__FILE__).'/inc/inc.php';
	C('test',false);
	C('start', gettimeofday( true ));
	$mid = getReq('cmd',1001);
	$key = getReq('k','74a0158682f1f4778ecedaea09d07c8b');
	$version = $ver = getReq('ver','1.0.0');
	$channel = getReq('ch');
	$serverId = getReq('sid',1);
	if( empty($mid) || empty($key) ){ //mid和key必须存在
		ret('cmd or key not null!',-1);
	}
	$mod = new Public_Modinit($mid,$key,$ver);
	$tag = $mod->name();
	$log = new Logger( $mod->tag() );
	$bin = file_get_contents('php://input');
	$input = msgpack_unpack( $bin );
	if( '' == getReq( 'cmd' ) )
		$input = json_decode('{"sid":1,"rid":616}',true);
	if( C('test') ){
		$showTag = $tag.'=DB模式=';
	}else{
		$showTag = $tag.'=cache模式=';
	}
	$log->f( '接口调用开始['.($ver?$ver:0).'] - '.$showTag.' IP:'.long2ip(getIp()) );
	$log->d(json_encode($_REQUEST));
	$log->d(json_encode($input));
	$filepath = $mod->path();
	unset($mod);
	if( is_file( BOOT.$filepath ) ){
		require BOOT.$filepath;
	}else{
		ret('Module file not exist!',-1);
	}
