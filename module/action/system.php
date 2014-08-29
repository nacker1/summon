<?php
/**
 *@ 兑换码接口
 **/

 $user = new User_User();

 $type = isset( $input['t'] ) ? $input['t'] : 1;
 switch( $type ){
 	case '1': //兑换码兑换
 		$tag = ' 兑换码兑换 ';
		$code = $input['code'];
		if(  8 != strlen( $code ) ){
			ret( '兑换码有误',-1 );
		}
		$c = new Act_Code( $code );
		$config = $c->getConfig();
		if( !$config ){
			ret( $c->getExchangeInfo(), -1 );
		}else{
			$ret = $user->sendGoodsFromConfig( $config );
		}
		ret( $ret );
	case '2'://用户反馈
		$tag = ' 用户反馈 ';
		$con = $input['con'];
		$type = $input['type'];
		$feedback = new User_Feedback( $type );
		$feedback->putContents( $con );
		ret('suc');
 }
?>