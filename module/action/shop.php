<?php
/**
 *@ 商店接口
 **/
 $user = new User_User();
 
 $type = isset( $input['t']) ? $input['t'] : 1;
 
 $type = empty( $type ) ? 1 : $type;

 $shopId = isset( $input['sid']) ? $input['sid'] : 1;

 $tags = array(
 		1=>array('freeRefShopDay','普通商店','jewel'),
 		2=>array('vipRefShopDay','神秘商店','jewel'),
 		3=>array('arenaRefShopDay','竞技场商店','mArena'),
 		4=>array('endRefShopDay','远征商店','mAction')
 	);

 switch( $type ){
	case '1': //获取商店物品列表
		 $tag = '拉取商店'.$tags[$shopId][0][1].'物品';
		 $ref = isset( $input['ref']) ? $input['ref'] : 0;
		 if( !empty($ref) ){ //刷新普通商店次数添加
			$limit = new User_Limit( $user->getUid(), $tags[$shopId][0] );
			if( $limit->getLastFreeTimes() < 1 ){ //获取剩余免费次数
				$nums = $limit->getOneTimeCooldou(); //刷新商店所需要费用
				if( $nums>0 && $user->getTypeInfo( $tags[$shopId][2] ) < $nums ){
					ret('货币不足',-1);
				}else{
					$add[ $tags[$shopId][2] ] = -$nums;
				}
			}
			$limit->addLimitTimes(1);
		 }
		 $shop = new User_Shop( $user->getUid(),$shopId,$ref );
		 $goods = $shop->getShopGoods();
		 $u = $user->sendGoodsFromConfig($add);
		 ret( array_merge($goods,$u) );
	case '2': //购买物品
		$tag = '商店'.$tags[$shopId][0][1].'购买商品';
		$index = isset( $input['index']) ? $input['index'] : 0;
		$shop = new User_Shop( $user->getUid(),$shopId );
		$shopInfo = $shop->getItemInfo( $index );
		if( empty( $shopInfo ) ){
			ret( '商品不存在',-1 );
		}
		if( empty( $shopInfo['status'] ) ){
			ret('物品已售馨',-1);
		}
		switch( $shopInfo['type'] ){
			case '1'://金币购买
				if( $user->getMoney() < $shopInfo['price'] ){
					$error = true;
				}
				$add['money'] = -$shopInfo['price'];break;
			case '2'://钻石购买
				if( $user->getCooldou() < $shopInfo['price'] ){
					$error = true;
				}
				$add['jewel'] = -$shopInfo['price'];break;
			case '3'://竞技场币购买
				if( $user->getUserRecord( 'mArena' ) < $shopInfo['price'] ){
					$error = true;
				}
				$add['mArena'] = -$shopInfo['price'];break;
			case '4'://远征币购买
				if( $user->getUserRecord( 'mAction' ) < $shopInfo['price'] ){
					$error = true;
				}
				$add['mAction'] = -$shopInfo['price'];break;
			default:
				ret('天空飘来3个字~~  什么字~~',-1);
		}
		if( $error ){ //货币不足
			ret( 'no_enough_coin', -1 );
		}

		$user->setMissionId(2,63);
		$shop->setItemStatus($index);
		$add['good'] = array( $shopInfo['gid'].','.$shopInfo['nums'] );
		$ret = $user->sendGoodsFromConfig( $add );
		#=========== 任务信息 ==================
		$mis = $user->getMissionNotice();
		if( !empty( $mis ) ){
			$ret['mis'] = $mis;
		}
		ret( $ret );
	case '3':
		$shop = new User_Shop( $user->getUid(),2 );
		$shop->getTypeItems();
		ret('suc');
	default:
		ret('看~~ 腿毛~ ',-1);
 }
?>