<?php
/**
 *@抽卡活动
 **/
	$user = new User_User();

	$type = isset( $input['t'] ) ?  $input['t'] : 1;

	$num = $nums = isset( $input['n'] ) ?  $input['n'] : 1;

	if( $num%10==0 ){
		$nums = $num*0.9; 	//10连抽打9折
	}

	switch( $type ){
		case '1': //金币抽扣金币
			$limit = new User_Limit( 'goldDrawDay' );
			$money = $limit->getOneTimeCooldou();
			$money = $money * $nums;
			if( $user->getMoney() < $money ){
				ret( 'no_money',-1 );
			}
			$give['money'] = -$money;
			$limit->addLimitTimes();
			break;
		case '2'://钻石抽钻石
			$limit = new User_Limit( 'jewelDrawDay','dayLimit', 3600*48 );
			$cooldou = $limit->getOneTimeCooldou();
			
			$cooldou = $cooldou * $nums;
			if( $user->getCooldou() < $cooldou ){
				ret( 'no_jewel',-1 );
			}
			$give['cooldou'] = -$cooldou;
			$limit->addLimitTimes( $nums );
			break;
		case '3'://友情点抽友情点
			$limit = new User_Limit( 'friendDrawDay','dayLimit', 3600*48 );
			
			$cooldou = $limit->getOneTimeCooldou();
			
			$cooldou = $cooldou * $nums;
			if( $user->getUserRecord( 'mFriend' ) < $cooldou ){
				ret( 'no_friendCoin',-1 );
			}
			$give['mFriend'] = -$cooldou;
			$limit->setTimeLimit();
			break;
	}
	
	$draw = new User_Draw( $type );

	$give['good'] = $draw->getGift( $num );

	$ret = $user->sendGoodsFromConfig($give);

	$ret['get'] = $give['good'];

	ret( $ret );

?>