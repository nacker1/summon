<?php
/**
 *@ 物品发放接口
 **/
$type = $input['t'];
$nums = $input['n'];
$user=new User_User();

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
	case '997':
		$user->setLoginTime();
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
			if( $v['Item_Id'] > 90000 )continue;
			$good = new User_Goods($user->getUid(), $v['Item_Id']);
			$good->addGoods(10);
		}
		ret('suc');
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