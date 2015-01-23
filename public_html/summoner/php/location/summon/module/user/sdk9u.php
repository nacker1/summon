<?php
/**
 *@ 九游sdk
 **/
/*	$t = $input[ 't' ]; //类型  t=1时用于登录
	switch( $t ){
		case '1':*/
			require dirname(dirname(__DIR__)).'/inc/inc.php';
			#$sid = $input['sid'];
			$url = 'http://sdk.g.uc.cn/cp/account.verifySession';
			$curl = new Curl( $url );
			$req['id'] = time();
			$req['data'] = array('sid'=>'ssh1game523fb7448bb946b28a3f8c2c9da2a5af107810');
			$req['game'] = array('gameId'=>552100);
			$req['sign'] = md5('sid=ssh1game523fb7448bb946b28a3f8c2c9da2a5af10781040b281c1f0359a7978174682539bd7ac');
			var_dump( $curl->post( json_encode( $req ) ) );


?>