<?php
/**
 *  资源更新接口  
 *  更新内容：
 *	1、每日限制使用次数
 *	2、按需更新背包信息，英雄信息，关卡进度
 **/
 $user = new User_User();

 $type = isset($input['t']) ? $input['t'] : 0;

 $types = explode( ',', $type );

 foreach( $types as $v ){
 	switch ($v) {
 		case '0': //获取用户当日限制使用次数
 			$cond = new Cond( 'dayLimit',$user->getUid() );
	  		$ret['dayLimit'] = $cond->getAll('',1);
	  		$ret['skill'] = $user->getUserSkillInfo();
	  		$sRedis = new Cond( 'userShop_2', $user->getUid() );
	  		$vshop = $sRedis->get();
	  		$ret['vshop'] = 1;
	  		if( empty( $vshop ) || !is_array( $vshop ) || count($vshop) < 1 )
	  			$ret['vshop'] = 0;
 			break;
 		case '1'://获取用户背包数据
 			$good = new User_Goods( $user->getUid() );
 			$ret['goods'] = $good->getAllGoods();
 			break;
 		case '2'://获取用户英雄数据
	 		$hero = new User_Hero( $user->getUid() );
 			$ret['heros'] = $hero->getUserHeroList();
 			break;
 		case '3'://获取用户关卡进度
 			$progress = new User_Progress();
 			$ret['progress'] = $progress->getUserProgressList();
 			break;
 	}
 }

 ret( $ret );
?>