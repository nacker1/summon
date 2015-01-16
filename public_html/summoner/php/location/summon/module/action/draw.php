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
	$give['good'] = array();
	switch( $type ){
		case '1': //金币抽扣金币
			$tag = '金币抽';
			$limit = new User_Limit( $user->getUid(), 'goldDrawDay' );
			if( $nums > 1 ){
				$money = $limit->getExpend();
			}else{
				$money = $limit->getOneTimeCooldou();
				$limit->addLimitTimes();
			}
			$money = $money * $nums;
			if( $user->getMoney() < $money ){
				ret( 'no_money',-1 );
			}
			$give['money'] = -$money;
			$money>0 && $give['good'][] = '63003,'.$num;
			break;
		case '2'://钻石抽钻石
			$tag = '钻石抽';
			$limit = new User_Limit( $user->getUid(), 'jewelDrawDay','dayLimit', 3600*48 );
			if( $nums > 1 ){
				$cooldou = $limit->getExpend();
			}else{
				$cooldou = $limit->getOneTimeCooldou();
				$limit->addLimitTimes();
			}
			
			$cooldou = $cooldou * $nums;
			if( $user->getCooldou() < $cooldou ){
				ret( 'no_jewel',-1 );
			}
			$give['cooldou'] = -$cooldou;
			$cooldou>0 && $give['good'][] = '63002,'.$num;
			break;
		case '3'://友情点抽友情点
			$tag = '友情点抽';
			$limit = new User_Limit( $user->getUid(), 'friendDrawDay','dayLimit', 3600*48 );
			
			if( $nums > 1 ){
				$cooldou = $limit->getExpend();
			}else{
				$cooldou = $limit->getOneTimeCooldou();
			}
			$limit->addLimitTimes( $nums );
			
			$cooldou = $cooldou * $nums;
			if( $user->getUserRecord( 'mFriend' ) < $cooldou ){
				ret( 'no_friendCoin',-1 );
			}
			$give['mFriend'] = -$cooldou;
			$give['good'][] = '63003,'.$num;
			break;
		case '10': #赏金之路钻石抽
			$tag = '赏金之路开宝箱';
			$roundnum = $input['roundnum'];  #赏金之路关卡id
			$actLimit = new User_Limit( $user->getUid(), 'endLessFieldDay' );
			if( $actLimit->getUsedTimes() < 1 ){
				#ret( '通关后地能抽取对应奖励',-1 );
			}
			$type = $input['gid'];
			switch ($type) {
				case '11': #普通宝箱
					break;
				case '12': #普通宝箱
					$give['cooldou'] = -50;
					break;
				case '13':
					$give['cooldou'] = -200;
					break;
				case '14':
					$give['cooldou'] = -500;
					break;
				default:
					ret( 'gid_error~'.__LINE__, -1 );
					break;
			}
			break;
		default:
			ret('YMD', -1);
	}

	$draw = new User_Draw( $type, $roundnum );

	$dGood = $draw->getGift( $num );

	$give['good'] = array_merge( $give['good'], $dGood );

	$ret = $user->sendGoodsFromConfig($give);

	$ret['get'] = implode('#',$dGood);
	
	ret( $ret );

?>