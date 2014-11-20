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
 			#初始化用户每日限制
 			$cond = new Cond( 'dayLimit',$user->getUid() );
	  		$ret['dayLimit'] = $cond->getAll('',1);

	  		#初始化用户技能点
	  		$ret['skill'] = $user->getUserSkillInfo();
			
	  		#初始化vip商店 
	  		$sRedis = new Cond( 'userShop_2', $user->getUid() );
	  		dump($sRedis);
	  		$ret['vshop'] = $sRedis->getTimes();
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
 		case '4':
 			#初始化任务系统
	  		$mis1 = new User_Mission( array( 'uid'=>$user->getUid(), 'type'=>1 ) );
	  		$mis2 = new User_Mission( array( 'uid'=>$user->getUid(), 'type'=>2 ) );
	  		$ret['mis'][1] = $mis1->getMissionList();
	  		$ret['mis'][2] = $mis2->getMissionList();
 	}
 }

 ret( $ret );
?>