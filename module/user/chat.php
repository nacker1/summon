<?php
/**
 *@ 聊天系统
 **/
 $user = new User_User();
 $type = isset( $input['t'] ) ? $input['t'] : '';

 if( empty( $type ) ){
 	ret( 'YMD', -1 );
 }

 switch( $type ){
 	case '1': #拉取信息
 		$lasttime = isset( $input['lt'] ) ? $input['lt'] : 0;
 		$chat = new Chat( $user->getUid() );
 		ret( $chat->getChat( $lasttime ) );
 	case '2': #发送信息
 		$to = !empty( $input['to'] ) ? $input['to'] : '';
 		$con = $input['con'];
 		$other = $input['other'];  #pvp|key 
 		$strLen = abslength($con);
 		if( empty( $con ) || $strLen < 1 || $strLen > 65 ){
 			ret( '字数在1-65之内'.$strLen, -1 );
 		}
 		if( empty( $to ) ){
	 		$limit = new User_Limit( 'helloWorld' );
	 		$money = $limit->getOneTimeCooldou();
	 		if( $user->getMoney() >= $money ){
	 			$limit->addLimitTimes();
	 			$chat = new Chat( $user->getUid() );
	 			$chat->sendChat( $con, $user->getUserName(), $user->getUid(), $user->getLevel(), $user->getImage(),$other );
	 			if( $money > 0 ){
	 				$give['money'] = -$money;
	 				$ret = $user->sendGoodsFromConfig( $give );
	 			}
	 			ret( array( 'money'=>$user->getMoney() ) );
	 		}
	 		ret( '喊话需要 '.$money.' 金币' );	
	 	}else{
	 		$chat = new Chat( $to,2 );
	 		$chat->sendChat( $con, $user->getUserName(), $user->getUid(), $user->getLevel(), $user->getImage(),$other );
	 		ret('发送成功');
	 	}
 }

 ret( 'YMD'.__LINE__, -1 );
?>