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
		$tag = '背包里使用物品';
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
				}
				if( isset( $gConfig['Action'] ) ){
					$add['life'] = $gConfig['Action'] * $nums ;
				}
				if( isset( $gConfig['time'] ) ){
					if( !$user->addRoleBuff( $gConfig ) ){
						ret('fail_'.__LINE__,-1);
					}
					$ret['buff'] = $gConfig;
				}
				if( isset( $gConfig['Cooldou'] ) ){
					$add['cooldou'] = $gConfig['Cooldou'] * $nums ;
				}
				if( isset( $gConfig['mArena'] ) ){
					$add['mArena'] = $gConfig['mArena'] * $nums ;
				}
				if( isset( $gConfig['mFriend'] ) ){
					$add['mFriend'] = $gConfig['mFriend'] * $nums ;
				}
				if( isset( $gConfig['mAction'] ) ){
					$add['mAction'] = $gConfig['mAction'] * $nums ;
				}
				if( empty( $add ) ){
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
					if( $hero->addHeroExp( $gConfig['Hero_Exp'] * $nums ) ){
						$good->reduceGoods( $nums );
						$ret['hero'] = $hero->getLastUpdField();
						$ret['list'] = $good->getLastUpdGoods();
						ret( $ret );
					}else{
						ret( '英雄已达最大等级', -1 );
					}
				}
			}
		}elseif( 65 == substr( $gid, 0 ,2 ) && is_numeric($gConfig) ){ //buff类药水特殊处理
			$good->reduceGoods( $nums );
			$ret['list'] = $good->getLastUpdGoods();
			$ret['buff'] = $user->addRoleBuff( $gConfig );
			ret( $ret );
		}
		ret( '你确定他能吃？', -1 );
	case '2': //物品出售
		$tag = '出售物品';
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
	case '3': #数据格式  list='hid,gid,nums#hid,gid,nums....'
		$tag = '英雄磕药';
		$list = $input['list'];
		if( empty( $list ) ){
			ret( 'no_con', -1 );
		}
		$list = explode('#',$list);
		if( !is_array( $list ) ){
			ret( 'con_err', -1 );
		}
		$nums = 0;
		foreach( $list as $v ){
			$info = explode(',',$v);
			if( isset( $gids[$info[1]] ) ){
				$gids[$info[1]] += $info[2];
			}else{
				$gids[$info[1]] = $info[2];
			}
			if( isset( $heros[ $info[0] ][ $info[1] ] ) ){
				$heros[ $info[0] ][ $info[1] ] += $info[2];
			}else{
				$heros[ $info[0] ][ $info[1] ] = $info[2];
			}
		}
		foreach( $gids as $k => $v ){
			$good[ $k ] = new User_Goods( $user->getUid(), $k );
			if( $good[ $k ]->getType() != 6 ){
				ret( '['.$good[ $k ]->getGoodName().']错误',-1 );
			}
			if( $good[ $k ]->getGoodsNum() < $v ){
				ret('['.$good[ $k ]->getGoodName().']不足'.__LINE__,-1);
			}
			$target[ $k ] = $good[$k]->getBaseConfig();
			if( !isset( $target[ $k ]['target'] ) ){
				ret( 'no_target_config', -1 );
			}
			if( 'hero' != $target[ $k ]['target'] ){
				ret( '此药药性太大，英雄承受不起', -1 );
			}
			if( !isset( $target[ $k ]['Hero_Exp'] ) ){
				ret( 'no_exp', -1 );
			}
		}
		foreach( $heros as $key=>$value ){
			foreach( $value as $k=>$v ){
				$hero = new User_Hero( $user->getUid(), $key );
				$hero->addHeroExp( $target[$k]['Hero_Exp'] * $v );
				$red_good[] = $k.','.$v; 
			}
		}
		$ret = $user->sendGoodsFromConfig( array('good'=>implode('#',$red_good) ) );
		$ret['hero'] = $hero->getLastUpdField();
		ret( $ret );
		break;
	default:
		ret('param error',-1);
 }
?>
