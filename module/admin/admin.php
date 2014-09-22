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
	$ret['hero'] = $hreo->getLastUpdField();
}

ret($ret);
?>