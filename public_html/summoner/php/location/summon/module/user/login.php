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
	$log->d('serverLastTime:'.$serverLast.', clientLastTime:'.$serverLastUpdTime);
	if( $serverLastUpdTime < $serverLast ){
		$ret['sinfo']['sList'] = $server->getServerList();
		$ret['sinfo']['slt'] = $serverLast;
	}
	$ret['sinfo']['sStatus'] = $server->getServersStatus();
	ret( $ret );
 }else{
	ret('错误：'.json_encode($input),-1);
 }
?>