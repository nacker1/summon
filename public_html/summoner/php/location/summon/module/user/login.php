<?php
/**
 *@ 通用登录接口
 **/
 $name = $input['n'];  //登录平台用户昵称
 $source = $input['s']; //登录平台id
 $source_id = $input['sid']; //登录平台角色id (唯一id)
 $channel = $input['cid']; //登录平台角色id (唯一id)
 $time = $input['ts'];	//登录时间
 $sign = $input['sign'];//校验
 $serverLastUpdTime = $input['slt'];
 $key = md5($name.$source.$source_id.$channel.$time);
 $input['key'] = $key;
 if( $key == $sign ){
	$user = new User_Reg( $source, $source_id , $name, $channel );
	$ret['uinfo'] = $user->getLoginInfo();
	$server = new Server();
	$serverLast = $server->getLastUpdTime();
	if( $serverLastUpdTime < $serverLast ){
		$ret['sinfo']['sList'] = $server->getServerList();
		$ret['sinfo']['slt'] = $serverLast;
	}
	/*if( '3729952484' == getIp() || '167773376' == getIp() || getIp() != '-1221026606' ){
		foreach( $ret['sinfo']['sList'] as $k=>$v ){
			$ret['sinfo']['sList'][$k]['php'] = 'http://183.56.156.211:8082';
		}
	}*/
	$ret['sinfo']['sStatus'] = $server->getServersStatus();
	ret( $ret );
 }else{
	ret('错误：'.json_encode($input),-1);
 }
?>