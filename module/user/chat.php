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
 		if( empty( $con ) || strlen( $con ) < 6 || strlen($con) > 60 ){
 			ret( '字数在2-60之内', -1 );
 		}
 		$limit = new User_Limit( 'helloWorld' );
 		$money = $limit->getOneTimeCooldou();
 		if( $user->getMoney() >= $money ){
 			$chat = new Chat( $to );
 			$chat->sendChat( $con, $user->getUserName(), $user->getUid() );
 			if( $money > 0 ){
 				$give['money'] = $money;
 				$ret = $user->sendGoodsFromConfig( $give );
 			}
 			ret( array( 'money'=>$user->getMoney() ) );
 		}
 		ret( '喊话需要 '.$money.' 金币' );	
 }

 ret( 'YMD'.__LINE__, -1 );
?>