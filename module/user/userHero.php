<?php
/**
 *@ 用户英雄接口
 **/
 $user = new User_User();
 
 $type = $input['t'];
 $hid = $input['hid'];
 switch( $type ){
	case '1'://获取用户所有英雄 
		$hero = new User_Hero( $user->getUid() );
		$heroList = $hero->getUserHeroList();
		if( empty( $heroList ) ){
			ret('no_hero',404);
		}else{
			ret($heroList);
		}
	case '999'://给英雄添加经验
		$hero = new User_Hero( $user->getUid(), $hid );
		ret($hero->getTotalFire());
		if( empty($hid) ){
			ret('param error ('.__line__.') !',-1);
		}
		$do = $input['do'];
		$to = $input['to'];
		switch ($do) {
			case '1': //添加经验
				$hero = new User_Hero( $user->getUid(), $hid );
				ret($hero->getHeroInfo());
				break;
			case '2':#颜色
				$hero = new User_Hero( $user->getUid(), $hid );
				$hero->colorUp($to);
				ret($hero->getHeroInfo());
			case '3':
				$hero = new User_Hero( $user->getUid(), $hid );
				ret($hero->delHeroInfo());
			default:
				# code...
				break;
		}
	case '3'://给英雄穿装备 equipInfo 格式=>( 英雄装备框id:装备id:用户道具id&装备id )
		if( empty($hid) ){
			ret('param error ('.__line__.') !',-1);
		}
		$equipInfo = $input['einfo'];
		if( empty($hid) || empty( $equipInfo ) ){
			ret('param error!',-1);
		}
		$equipConf = explode('&',$equipInfo);
		unset($equipInfo);
		if( empty($equipConf) || !is_array($equipConf) || count( $equipConf ) < 1 ){
			ret( 'param error!' , -1 );
		}
		$hero = new User_Hero( $user->getUid(), $hid );
		foreach( $equipConf as $v ){
			$equipInfo = explode(':',$v);
			$ug = new User_Goods( $user->getUid(), $equipInfo[1] );
			$bEquip = new Equipbase( substr( $equipInfo[1], 0, 5 ) );
			$log->i('heroLevel:'.$hero->getHeroLevel().', equipLevel:'.$bEquip->getEquipMinLevel());
			if( $hero->getHeroLevel() < $bEquip->getEquipMinLevel() ){
				ret( '英雄等级不够!', -1 );
			}
			$eGid = $hero->getHeroEquipGid( $equipInfo[0] );//卸下的兼备id
			
			if( $ug->getGoodsNum() > 0 ){
				if( !empty( $eGid ) ){//如果英雄本身有装备先取下装备再穿
					$downGoods = new User_Goods( $user->getUid(), $eGid );
					$gList[] = $eGid.',1';
				}
				$hero->heroPutOnEquip($equipInfo[0],$equipInfo[1]);
				$gList[] = $equipInfo[1].',-1';
			}else{
				ret('no_item_'.$equipInfo[1],-1);
			}
		}

		$give['good'] = implode('#',$gList);
		$ret = $user->sendGoodsFromConfig($give);
		$ret['hero'] = $hero->getLastUpdField();
		#=========== 任务信息 ==================
		$mis = $user->getMissionNotice();
		if( !empty( $mis ) ){
			$ret['mis'] = $mis;
		}
		ret( $ret );
	case '4'://取下英雄装备 equipInfo 格式=>(index&index 装备框下标&装备框下标)
		$index = $input['index'];
		if( empty($hid) || empty( $index ) ){
			ret('param error ('.__line__.') !',-1);
		}
		$indexList = explode('&',$index);
		unset($index);
		if( empty($indexList) || !is_array($indexList) || count( $indexList ) < 1 ){
			ret( 'param error('.__line__.')!' , -1 );
		}
		$hero = new User_Hero( $user->getUid(), $hid );
		foreach( $indexList as $v ){
			$goodid = $hero->getHeroEquipGid($v);
			if( empty($goodid) ){
				ret('装备已经取下',-1);
			}
			$ug = new User_Goods( $user->getUid(), $goodid );
			if( $ug->addGoods() ){
				$hero->heroPutDownEquip($v);
			}else{
				ret('error('.__line__.')',-1);
			}
		}
		$ret['hero'] = $hero->getLastUpdField();
		$ret['list'] = $ug->getLastUpdGoods();
		#=========== 任务信息 ==================
		$mis = $user->getMissionNotice();
		if( !empty( $mis ) ){
			$ret['mis'] = $mis;
		}
		ret( $ret );
	case '5': //英雄灵魂石召唤或提升品质
		$tag = '英雄合成或品质升级';
		if( empty( $hid ) ){
			ret('hero id error!',-1);
		}
		$hero = new User_Hero( $user->getUid(), $hid );
		$color = array( 1=>10,2=>20,3=>40,4=>80,5=>160 );
		$money = array(1=>5000,2=>10000,3=>20000,4=>40000,5=>80000);
		$gid = '11'.substr($hid,2);
		$goods = new User_Goods( $user->getUid(), $gid );
		$cLevel = 1;
		if( $hero->getHeroInfo() ){ //品质升级
			$hInfo = $hero->getHeroInfo();
			$cLevel = (int)$hInfo['color'] + 1;
			if( $cLevel >= 5 ){
				ret('品质已到顶级',-1);
			}
			if( $user->getMoney() < $money[ $cLevel ] ){
				ret( '升级需要'.$money[$cLevel].'金币', -1 );
			}

			if( $goods->getGoodsNum() >= $color[ $cLevel ] ){
				if( !$hero->colorUp( $cLevel ) ){
					ret('系统繁忙',-1);
				}
			}else{
				ret('灵魂石不足',-1);
			}
		}else{ //英雄合成
			if( $user->getMoney() < $money[ $cLevel  ] ){
				ret( '合成英雄需要'.$money[$cLevel ].'金币', -1 );
			}
			if( $goods->getGoodsNum() >= $color[ $cLevel  ] ){
				if( !$hero->giveHero() ){
					ret('系统繁忙',-1);
				}
			}else{
				ret('灵魂石不足',-1);
			}
		}
		$give['money'] = -$money[ $cLevel  ];
		$reGood[] = $gid.',-'.$color[ $cLevel ];
		$give['good'] = implode('#',$reGood);
		$ret = $user->sendGoodsFromConfig( $give );
		$ret['hero'] = $hero->getLastUpdField();
		#=========== 任务信息 ==================
		$mis = $user->getMissionNotice();
		if( !empty( $mis ) ){
			$ret['mis'] = $mis;
		}
		ret( $ret );
	case '6': //英雄技能升级
		$tag = '英雄技能升级';
		$sIndex = $input['sid']; //技能id
		if( empty( $hid ) ){
			ret('hero id error!',-1);
		}
		if( empty( $sIndex ) ){
			ret('skill id error!',-1);
		}
		$hero = new User_Hero( $user->getUid(), $hid );
		$hInfo = $hero->getHeroInfo();
		if( empty( $hInfo ) ){
			ret('error! '.__line__,-1);
		}

		$skillLevel = $hero->getSkillLevel( $sIndex );
		if( $skillLevel < $hero->getHeroLevel() ){ //技能等级不能超过当前英雄的等级

			$skill = new Skillcost( $sIndex, ( $skillLevel+1 ) ); //技能升级消耗金币类

			$cost = $skill->getCostMoney(); //技能升级需要扣除的金币数量
			if( empty( $cost ) ){
				ret('配置不存在',-1);
			}
			if( $user->getMoney() < $cost ){
				ret('金币不足,本次技能升级需要'.$cost.'金币',-1);
			}
			$heroSkillConfig = $hero->getSkillConfig();
			if( !isset( $heroSkillConfig[$sIndex] ) ){
				ret(' skill_unlock! ',-1);
			}
			if( $user->getUserSkillPoint() > 0 ){
				if( $user->reduceMoney( $cost ) ){
					$user->reduceUserSkillPoint();
					$hero->skillUp( $sIndex );
					$ret['hero'] = $hero->getLastUpdField();
					$ret['skill'] = $user->getUserSkillInfo();
					#=========== 任务信息 ==================
					$mis = $user->getMissionNotice();
					if( !empty( $mis ) ){
						$ret['mis'] = $mis;
					}
					ret( $ret );
				}else{
					ret('服务器繁忙，请重试！',-1);
				}
			}else{
				ret('技能点不足',-1);
			}
		}else{
			ret('技能等级不能超过英雄等级',-1);
		}
	case '7':#一次性发放前期所有英雄
		$hList = $input['heros'];
		if( empty( $hList ) ){
			$hList = array( 11001,11002,11004,11005,11006,11008,11009,11010,11011,11012,11013,11015,11018,11019,11021,11022,11023,11024,11025,11026,11027,11028,11029,11031,11032,11034,11036,11040 );
		}
		foreach ( $hList as $v ) {
			$hero = new User_Hero( $user->getUid(),$v );
			$hero->giveHero();
			#unset($hero);
		}
		$ret['hero'] = $hero->getLastUpdField();
		#=========== 任务信息 ==================
		$mis = $user->getMissionNotice();
		if( !empty( $mis ) ){
			$ret['mis'] = $mis;
		}
		ret( $ret );
	default:
		ret('type error!',-1);
 }
?>