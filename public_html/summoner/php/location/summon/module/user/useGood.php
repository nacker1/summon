<?php
/**
 *@ 召唤师消耗品使用
 **/
 $user = new User_User();
 $type = $input['t'];
 //$good = new User_Goods( $user->getUid(),63002 );
 //$good->addGoods(100);
 //exit;
 switch( $type ){
	case '1': //基础消耗品使用 如体力药水
		$gid = $input['gid'];	//物品id
		$nums = isset( $input['nums'] ) && is_numeric( $input['nums'] ) && $input['nums'] > 0 ? $input['nums'] : 1;	//消耗物品数量
		$good = new User_Goods( $user->getUid(),$gid );
		if( $good->getType() != 6 ){
			ret( '物品错误',-1 );
		}
		if( $good->getGoodsNum() < $nums ){
			ret('没有足够的物品'.__LINE__,-1);
		}
		$bGood = new Goodbase( $gid );
		$gConfig = $bGood->getGoodConfig();
		if( isset( $gConfig['target'] ) ){
			$ret = array();
			if( 'role' == $gConfig['target'] ){ //消耗品针对召唤师
				unset($gConfig['target']);
				if( isset( $gConfig['Money'] ) ){
					$add['money'] = $gConfig['Money'] * $nums ;
				}elseif( isset( $gConfig['Action'] ) ){
					$add['life'] = $gConfig['Action'] * $nums ;
				}elseif( isset( $gConfig['time'] ) ){
					if( !$user->addRoleBuff( $gConfig ) ){
						ret('fail_'.__LINE__,-1);
					}
					$ret['buff'] = $gConfig;
				}else{
					ret('config fail_'.__LINE__);
				}
				$add['good'] = $gid.',-'.$nums;
				$result = $user->sendGoodsFromConfig( $add );
				$ret = array_merge( $ret, $result );
				ret( $ret );
			}elseif( 'hero' == $gConfig['target'] ){ //消耗品针对英雄 
				$hid = $input['hid'];	//英雄id
				if( empty( $hid ) ){
					ret('hid error~',-1);
				}
				$hero = new User_Hero( $user->getUid(), $hid );
				if( isset( $gConfig['Hero_Exp'] ) ){
					if( $good->getGoodsNum() < $nums ){
						ret('物品数量不足',-1);
					}
					if( $hero->addHeroExp( $gConfig['Hero_Exp'] ) ){
						$good->reduceGoods( $nums );
						$ret['hero'][$hid] = $hero->getHeroLevelAndExp();
						$ret['list'] = $good->getLastUpdGoods();
						ret( $ret );
					}else{
						ret( '英雄已达最大等级', -1 );
					}
				}
			}
		}elseif( 65 == substr( $gid, 0 ,2 ) && is_numeric($gConfig) ){ //buff类药水特殊处理
			ret( $user->addRoleBuff( $gConfig ) );
		}

		ret($gConfig);
	case '2': //物品出售
		$gid = $input['gid'];	//物品id
		$ugid = $input['ugid'];	//用户拥有物品id
		$nums = isset( $input['nums'] ) && is_numeric( $input['nums'] ) && $input['nums'] > 0 ? $input['nums'] : 1;	//消耗物品数量
		$good = new User_Goods( $user->getUid(),$gid,$ugid );
		if( $good->getGoodsNum() < $nums ){
			ret('没有足够的物品',-1);
		}
		if( $good->reduceGoods( $nums , 'del' ) !== false ){
			$bGood = new Goodbase( $gid );
			$price = $bGood->getSellPrice();
			if( $user->addMoney( $nums * $price ) ){
				ret( array( 'money'=>$user->getMoney(),'list'=>$good->getLastUpdGoods() ) );
			}else{
				$log->e('* 给用户#'.$user->getUid().'#添加#'.($nums * $price).'#金币失败 ');
				$good->addGoods( $nums ); //退还用户道具
				ret('fail_'.__LINE__,-1);
			}
		}else{
			ret('no_enough_item'.__LINE__,-1);
		}
	default:
		ret('param error',-1);
 }
?>