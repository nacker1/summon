<?php
/**
 *@ buy 用户购买行为
 **/
 $user = new User_User();
 $type = isset( $input['t'] ) ? $input['t'] : 0;

 if( empty( $type ) ){
 	ret('YMD',-1);
 }

 $config = array(
 		1=>array('name'=>'购买体力'		,'tag'=>'buyLifeDay'),
 		2=>array('name'=>'点金手'		,'tag'=>'exchangeMoneyTimesDay'),
 		3=>array('name'=>'购买竞技场次数'	,'tag'=>'buyArenaTimesDay'		,'to'=>'doArenaTimesDay'),
 		4=>array('name'=>'消除竞技场冷却时间'	,'tag'=>'delAreanTimeDay'		,'to'=>'doArenaTimesDay'),
 		5=>array('name'=>'购买技能点次数'	,'tag'=>'buyPointDay' ),
 		6=>array('name'=>'购买精英关卡次数'	,'tag'=>'resetEliteTimesDay', 'to'=>'customsTimesDay' ),
 		7=>array('name'=>'购买炼狱关卡次数'	,'tag'=>'resetGaolTimesDay', 'to'=>'customsTimesDay' ), 
 		8=>array('name'=>'战争学院敲醒次数'	,'tag'=>'buyStrikeTimesDay', 'to'=>'strikeTimesDay' ), #战争学院敲醒次数
	);
 if( !isset( $config[$type] ) ){
 	ret('YMD', -1);
 }
 $tag = $config[$type]['name'];
 $limit = new User_Limit( $user->getUid(), $config[$type]['tag'] );
 if( $limit->getLastTimes() < 1 ){
 	ret( ' 购买次数已达上限 ',-1);
 }
 switch ($type) {
 	case '1': //购买体力 
 		$tag = '购买体力';
 		$cooldou = $limit->getOneTimeCooldou();
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$add['life'] = $limit->getGiveNum();
 			$ret = $user->sendGoodsFromConfig($add);
 			#$ret['mis'] = $user->getMissionNotice();
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	case '2'://点金手 		$u
 		$tag = '点金手';
		$times = $limit->getUsedTimes();
		$reCooldou = true;
		$buyGold = new Buy( 'buyGold', ($times+1) );
		$config = $buyGold->getConfig();
		
		$reCooldou = $user->reduceCooldou( $config['BuyGold_Cost'] );
	
		if( $reCooldou !== false ){
			$rates[1] = $config['BuyGold_Rate1']/10000;
			$rates[2] = $config['BuyGold_Rate2']/10000;
			$rates[5] = $config['BuyGold_Rate3']/10000;
			$rates[10] = $config['BuyGold_Rate4']/10000;
			$rate = retRate( $rates );
			if( !isset( $rates[ $rate ] ) ){
				$rate = 1;
			}else{
				$rate = $rate ;
			}

			$good['money'] = $config['BuyGold_Get'] * $rate;
			$user->setMissionId( 2, 67 );
			$ret = $user->sendGoodsFromConfig(json_encode($good));
			$ret['addMoney'] = $good['money'];
			$ret['rate'] = $rate;
			$ret['times'] = $times+1;
			#$ret['mis'] = $user->getMissionNotice();
		}else{
			ret( '钻石不足',-1 );
		}
 		break;
 	case '3': //购买竞技场战斗次数
 		$tag = '购买竞技场战斗次数';
 		$cooldou = $limit->getOneTimeCooldou();
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$toLimit = new User_Limit( $user->getUid(),$config[$type]['to'] );
 			$toLimit->delLimit();
 			$ret = $user->sendGoodsFromConfig($add);
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	case '4': //消除竞技场冷却时间
 		$tag = '消除竞技场冷却时间';
 		$cooldou = $limit->getOneTimeCooldou();
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$toLimit = new User_Limit( $user->getUid(), $config[$type]['to'] );
 			$toLimit->delTimeLimit();
 			$ret = $user->sendGoodsFromConfig($add);
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	case '5': //购买技能点
 		$tag = '购买技能点';
 		$cooldou = $limit->getOneTimeCooldou();
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$user->addUserSkillPoint( $limit->getGiveNum() );
 			$ret = $user->sendGoodsFromConfig($add);
 			$ret['skill'] = $user->getUserSkillInfo();
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	case '6': //购买精英关卡
 		$tag = '购买困难关卡次数';
 		$roundid = $input['roundid'];  #关卡id
 		if( empty( $roundid ) ){ ret(' YMD'.__LINE__,-1); }
 		$cooldou = $limit->getOneTimeCooldou( $roundid );
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$toLimit = new User_Limit( $user->getUid(), $config[$type]['to'] );
 			$toLimit->delLimit( $roundid );
 			$ret = $user->sendGoodsFromConfig($add);
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	case '7': //购买炼狱关卡
 		$tag = '购买炼狱关卡次数';
 		$roundid = $input['roundid'];  #关卡id
 		if( empty( $roundid ) ){ ret(' YMD'.__LINE__,-1); }
 		$cooldou = $limit->getOneTimeCooldou( $roundid );
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$toLimit = new User_Limit( $user->getUid(), $config[$type]['to'] );
 			$toLimit->delLimit( $roundid );
 			$ret = $user->sendGoodsFromConfig($add);
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	case '8': //购买战争学院敲醒次数
 		$tag = '购买战争学院敲醒次数';
 		$cooldou = $limit->getOneTimeCooldou( $roundid );
 		if( $user->getCooldou() >= $cooldou ){
 			$add['jewel'] = -$cooldou;
 			$toLimit = new User_Limit( $user->getUid(), $config[$type]['to'] );
 			$toLimit->delLimit();
 			$ret = $user->sendGoodsFromConfig($add);
 		}else{
 			ret( 'no_jewel', -1 );
 		}
 		break;
 	default:
 		# code...
 		break;
 }
 $limit->addLimitTimes(1,$roundid);
 ret( $ret );

?>