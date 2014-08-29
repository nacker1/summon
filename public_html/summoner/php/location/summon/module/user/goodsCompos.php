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

			$give['money'] = -$money;
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
			$give['good'] = implode('#',$gTemp);
			$ret = $user->sendGoodsFromConfig( $give );

			ret( $ret );
		 }else{
			 ret('no_config_'.__LINE__,-1);
		 }
	case '2': //装备强化
		$tag = '装备强化';
 		$comid = $input['comid']; 	//需要强化的装备id
 		$iList = $input['items'];		//需要吞掉的物品id
 		if( empty( $comid ) || empty( $iList ) ){
 			ret('YMD('.__LINE__.')',-1);
 		}

 		$iList = explode( '#', $iList );

 		$good = new User_Goods( $user->getUid(),$comid );

 		if( $good->getGoodsNum() < 1 ){
 			ret( '['.$good->getGoodName().']不足，强化失败。', -1 );
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
 			$tolEnergy += $gTemp->getValue()*$good[1];
 		}

 		if( $tolEnergy < $consumeEnergy ){
 			$log->e( '用户'.$user->getUid().'强化装备【'.$gCom->getGoodName().'】失败，能量值不足'.$consumeEnergy.' > '.$tolEnergy );
 			ret( 'no_enough_energy_'.__LINE__, -1 );
 		}

 		$goods[] = $comid.',-1';
 		$goods[] = ($comid+1).',1';

 		$give['good'] = implode( '#', $goods );

 		$ret = $user->sendGoodsFromConfig( $give );
 		ret($ret);
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