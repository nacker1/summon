<?php
/**
 *@ 通用登录接口
 **/
 $name 				= $input['n'];  			#登录平台用户昵称
 $source 			= $input['s']; 				#登录平台id
 $source_id 		= $input['sid']; 			#登录平台角色id (唯一id)
 $channel 			= $input['cid']; 			#登录平台角色id (唯一id)
 $time 				= $input['ts'];				#登录时间
 $sign 				= $input['sign'];			#校验
 $ver  				= (int)$input['ver'];			#版本id  用户检测热更新
 $serverLastUpdTime = $input['slt'];			#客户端本地服务器列表最后更新的时间
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
	$ret['down']['ver'] = $server->getServerVer();
	$ret['down']['url'] = 'http://summon.51094.com/download/2.zip';
	$ret['down']['size'] = filesize( ROOT.'/download/2.zip' );
	if( $ret['down']['ver'] - $ver < 2 ){
		$ret['down']['url'] = 'http://summon.51094.com/download/1.zip';
		$ret['down']['size'] = filesize( ROOT.'/download/1.zip' );
	}
	ret( $ret );
 }else{
	ret('错误：'.json_encode($input),-1);
 }
?>