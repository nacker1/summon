<?php
/**
 *@ 物品发放接口
 **/
$type = $input['t'];
$nums = $input['n'];
$uid = $input['uid'];
$user=new User_User( $uid );

switch ($type) {
	case '1':
		# code...
		$hid = $input['hid'];
		if( empty( $hid ) ){
			$user->addExp( $nums );
		}else{
			$hero = new User_Hero( $user->getUid(), $hid );
			$hero->addHeroExp( $nums );
		}
		break;
	case '2':
		#添加vip
		if( $nums>15 || $nums<0 ){
			ret( 'vip 等级在  1 - 15级之间', -1 );
		}
		$user->setVip( $nums );
		break;
	case '3':
		#添加用户体力
		$user->addLife( $nums );
		ret($user->getUserLastUpdInfo());
	case '4':
		#添加用户周卡
		$n = isset($input['n']) ? $input['n'] : 3;
		switch( $n ){
			case '1':
				$user->setMonthCode();
				break;
			case '2':
				$user->setWeekCode();
				break;
			default:
				$user->setMonthCode();
				$user->setWeekCode();
		}
		ret( $user->getUserLastUpdInfo() );
	case '997': #踢下线
		$user->setLoginTime();
		$user->setSkey();
		ret( $user->getUserInfo() );
	case '998':
		#清空所有配置缓存
		$cache = array(
			'baseDrawConfig:*','baseDrawTypeConfig:*','baseMissionConfig:*','shopConfig:*','action:sign:*',
			'baseBuffConfig:*','baseBuyGoldConfig:*','equip:baseinfo*','goodBase:base*','goodBase:equip*',
			'goodBase:compos*','heroSkillCost:*','heroBase:*','roleLevelUp*','vipConfig*','server:list:*'
		);
		$user->clearConfig( $cache );
		ret('suc');
		break;  
	case '999':
		#添加所有道具
		$gBase = new Goodbase();
		$gList = $gBase->getAllBaseGood();
		foreach( $gList as $v ){
			if( substr($v['Item_Id'],0,1) == 9 )continue;
			$good = new User_Goods($user->getUid(), $v['Item_Id']);
			$good->addGoods(10);
		}
		ret('suc');
		break;
	case '1000':
		#添加所有英雄
		$hList = $input['heros'];
		if( empty( $hList ) ){
			$hList = array( 10001,10002,10004,10005,10006,10008,10009,10010,10011,10012,10013,10015,10018,10019,10021,10022,10023,10024,10025,10026,10027,10028,10029,10031,10032,10034,10036,10040 );
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
	case '1001': #发送邮件
		
		break;
	default:
		# code...
		phpinfo();
		ret( ' YMD '.__LINE__, -1 );
		break;
}

$ret = $user->getUserLastUpdInfo();
$mis = $user->getMissionNotice();
if( !empty( $mis ) ){
	$ret['mis'] = $mis;
}

if( isset( $hero ) && gettype( $hero ) == 'object' ){
	$ret['hero'] = $hero->getLastUpdField();
}

ret($ret);
?>