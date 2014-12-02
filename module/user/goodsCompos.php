<?php
/**
*@ 装备处理逻辑
*@ param 
*	t: 接口处理类型   1为装备合成，2 为装备强化
*	
**/
 $user = new User_User();
 
 $type = isset( $input['t'] ) ? $input['t'] : 1;

 switch ($type) {
 	case '1': //装备合成
 		$tag = '装备合成';
 		$comid = $input['comid']; //需要合成的装备id

		 if( $comid < 30000 ){
			ret('YMD',-1);
		 }

		 $goodcom = new Goodcompos( $comid );

		 $cominfo = $goodcom->getComItemInfo();

		 $config = $cominfo['config'];
		 if( !empty( $config ) ){
			$money = $cominfo['Cost_Gold'];
			$uMoney = $user->getMoney();
			if( $uMoney < $money ){
				$log->e('* 用户'.$user->getUid().'金币不足，合成失败。装备：'.$config);
				ret('还差（'.($money-$uMoney).'）金币才能合成。',-1);
			}

			$gTemp = array();
			$goods = explode('&',$config);
			$i = 0;
			foreach( $goods as $val ){
				$good = explode(':',$val);

				$ug[$i] = new User_Goods( $user->getUid(), $good[0] );
				if( $ug[$i]->getGoodsNum() < $good[1] ){
					$log->e('* 用户'.$ug[$i]->getGoodName().'不足，无法合成。装备：'.$config);
					ret('['.$ug[$i]->getGoodName().']不足，无法合成。',-1);
				}else{
					$g[] = $good[0];
					$g[] = -$good[1];
					array_push( $gTemp, implode(',',$g) );
					unset($g);
				}
				$i++;
			}
			array_push( $gTemp, $comid.',1' );
			$give['money'] = -$money;
			$give['good'] = implode('#',$gTemp);
			$ret = $user->sendGoodsFromConfig( $give );

			ret( $ret );
		 }else{
			 ret('no_config_'.__LINE__,-1);
		 }
	case '2': //装备强化
		$tag = '装备强化';
 		$comid = $input['comid']; 					//需要强化的装备id
 		$iList = $input['items'];					//需要吞掉的物品id
 		$hid = $input['hid'];						//英雄id
 		$index = $input['index'];					//英雄需要强化装备对应的装备框
 		if( empty( $comid ) || empty( $iList ) ){
 			ret('YMD('.__LINE__.')',-1);
 		}

 		$iList = explode( '#', $iList );

 		$good = new User_Goods( $user->getUid(),$comid );

 		# 强化背包中的物品  hid存在则强化用户身上的装备
 		if( empty( $hid ) ){ 
 			if( $good->getGoodsNum() < 1 ){
	 			ret( '['.$good->getGoodName().']不足，强化失败。', -1 );
	 		}
 			$goods[] = $comid.',-1';
 		}

 		$gCom = new Goodcompos( $comid, 2 );

 		$consumeEnergy = $gCom->getEnergy();

 		if( empty( $consumeEnergy ) ){
 			$log->e( '用户#'.$user->getUid().'#强化装备【'.$gCom->getGoodName().'】失败，强化没有能量点配置，已达强化最大级别。' );
 			ret( 'max_level_'.__LINE__, -1 );
 		}

 		$tolEnergy = 0 ;
 		foreach( $iList as $v ){
 			$good = explode( ',', $v );
 			if( !isset( $good[1] ) )ret('error_items_'.__LINE__, -1);
 			$gTemp = new User_Goods( $user->getUid(), $good[0] );
 			if( $gTemp->getGoodsNum() < $good[1] ){
 				ret( 'no_item_'.$v, -1 );
 			}
 			$goods[] = $good[0].',-'.$good[1];
 			$tolEnergy += (int)( $gTemp->getValue()*$good[1] );
 		}

 		if( $tolEnergy < $consumeEnergy ){
 			$log->e( '用户'.$user->getUid().'强化装备【'.$gCom->getGoodName().'】失败，能量值不足'.$consumeEnergy.' > '.$tolEnergy );
 			ret( 'no_enough_energy_'.__LINE__, -1 );
 		}
 		$user->setMissionId( 2,37 );
 		
 		$result=array();
 		$ret = array();

 		if( empty( $hid ) ){ 
 			$goods[] = ($comid+1).',1';
 		}else{
 			$hero = new User_Hero( $uid, $hid );
 			$hero->heroPutOnEquip( $index, $comid+1 );
 			$result['hero'] = $hero->getLastUpdField();
 		}
 	#======================  设置强化任务   ==========================
 		$proxy = new Proxy( array('type'=>1,'uid'=>$user->getUid()), 'User_Mission', 'getUserMissingByClass' );
		$miss = $proxy->exec( 36 );
		if( $miss['progress']<($comid+1)%100 ){
			$user->setMissionId( 1, 36 );
		}
		if( $miss['missing'] > 136001 ){
			$money = $consumeEnergy * 150;
			if( $user->getMoney() < $money ){
	 			ret( 'no_money', -1 );
	 		}
	 		$give['money'] = -$money;
		}
 	#======================  设置强化任务   ==========================
 		$give['good'] = implode( '#', $goods );

 		$ret = $user->sendGoodsFromConfig( $give );
 		#=========== 任务信息 ==================
		$mis = $user->getMissionNotice();
		if( !empty( $mis ) ){
			$ret['mis'] = $mis;
		}
 		ret( array_merge($ret, $result) );
 	case '3'://给用户发放物品接口
 		$gid   = isset($input['g']) ? $input['g'] : 0;
 		$num = isset($input['n']) ? $input['n'] : 10;
 		if( empty( $gid ) ){
 			ret( '物品id不对',-1 );
 		}
 		if( empty( $num ) ){
 			$num = 1;
 		}
 		/*$good = new User_Goods( $user->getUid(),$gid );
 		$good->addGoods($num);
 		ret( $good->getLastUpdGoods() );*/
 		$mail = new User_Mail();
 		$mail->sendMail('管理后台发送物品',2,$user->getUid(),'后台发物品',json_encode(array('good'=>$gid.','.$num)) );
 		ret('已发送到邮箱，请查收！');
 	default:
 		# code...
 		break;
 }

?>