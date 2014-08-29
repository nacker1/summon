<?php
/**
 *@ 进入游戏接口
 **/
	 $rid = $input['rid'];
	 $sid = $input['sid'];
	 
	 $server = new Server($sid); //进入游戏时判断服务器是否已停服

	 $status = $server->getServersStatus();

	 if( 0 < $status['close'] ){
		$cInfo = empty( $status['cInfo'] ) ? '服务器正在维护，请稍等' : $status['cInfo'];
		ret($cInfo,-2);
	 }
	 
	 $user = new User_Login( $rid,$sid );

	 $userinfo = $user->getUserBeginInfo();
	
	 ret( $userinfo );
?>