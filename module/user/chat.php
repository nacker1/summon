<?php
/**
 *@ 聊天系统
 **/

 $type = isset( $input['t'] ) ? $input['t'] : '';

 if( empty( $type ) ){
 	ret( 'YMD', -1 );
 }

 switch( $type ){
 	case '1': #拉取信息
 		$chat = new Chat( $user->getUid() );
 		ret( $chat->getChat() );
 		break;
 	case '2': #发送信息
 		$to = !empty( $input['to'] ) ? $input['to'] : '';
 		$con = $input['con'];
 		if( empty( $con ) || strlen( $con ) < 6 || strlen($con) > 60 ){
 			ret( '字数在2-60之内', -1 );
 		}
 		$limit = new User_Limit( 'helloWorld' );
 		$money = $limit->getOneTimeCooldou();
 		if( $user->getMoney() >= $money ){
 			$chat = new Chat($to);	
 		}else{

 		}
 		ret();	
 		break;
 }

 ret( 'YMD'.__LINE__, -1 );
?>