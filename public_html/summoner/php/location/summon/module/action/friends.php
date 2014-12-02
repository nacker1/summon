<?php
/**
 *@ friends 好友接口
 **/
 
 $user = new User_User();

 $type = $input['t'];

 if( empty( $type ) ){
 	ret( 'YMD',-1 );
 }
 switch ($type) {
 	case '1': //查找好友
 		$tag = '查找好友';
 		$find = isset($input['find']) ? $input['find'] : null; 			//$find 查找的用户名
 		if( empty($find) || $user->getUserName() == $find ){
 			ret( 'YMD_'.__LINE__,-1 );
 		}
 		$filter = new Filter( $find );
 		$find = $filter->filterSql();						//过滤SQL防注入
 		$friend = new User_Friend(  );
 		$fInfo = $friend->getInfo( $find );
 		if( is_array( $fInfo ) ){
	 		$uid = $fInfo['id'];
	 		$friend = new User_Friend( $user->getUid(),$uid );
	 		if( $friend->sendInvite() ){
	 			ret( 'suc' );
	 		}
	 	}else{
	 		ret( '好友名称不存在',-1 );
	 	}
 	case '2':	//邀请好友
 		$tag = '邀请好友';
 		$to = isset( $input['to'] ) ? $input['to'] : 0 ;
 		if( empty( $to ) ){
 			ret( ' YMD_'.__LINE__,-1 );
 		}
 		if( $to == $user->getUid() ){
 			ret( '您不能添加自己为好友。',-1 );
 		}
 		$friend = new User_Friend( $user->getUid(),$to );
 		if( $friend->sendInvite() ){
 			ret( 'suc' );
 		}else{
 			ret( $friend->getErrorInfo() , -1 );
 		}
 	case '3':	//拉取邀请列表
 		$tag = '拉取邀请列表';
 		$friend = new User_Friend();
 		$ret['list'] = $friend->getInviteLists();
 		ret( $ret );
 	case '4'://同意好友添加邀请
 		$tag = '同意好友添加邀请';
 		$to = isset( $input['to'] ) ? $input['to'] : 0 ;
 		$opt = isset( $input['opt'] ) ? $input['opt'] : 1 ;
 		if( empty( $to ) || $to == $user->getUid() ){
 			ret( ' YMD_'.__LINE__,-1 );
 		}

 		$friend = new User_Friend( $user->getUid(), $to );
 		if( 1==$opt ){
 			if( $friend->isFriend( $to ) ){ ret( '添加成功',-1 ); }
 			$ret = $friend->agreeUserInvite();
 		}else{
 			$ret = $friend->delInvite();
 		}

 		if( $ret ){
 			ret('suc---');
 		}else{
 			ret( $friend->getErrorInfo(),-1 );
 		}
 	case '5'://拉好友列表
 		$tag = '拉好友列表';
 		$friend = new User_Friend();
 		$ret['list'] = $friend->getFriendList();
 		ret($ret);
 	case '6'://赠送体力  giveLifeDay
 		$tag = '赠送体力';
 		$to = $input['to'];
 		if( empty( $to ) ){
 			ret('YMD',-1);
 		}
 		if( $to == $user->getUid() ){
 			ret('YMD',-1);
 		}
 		$limit = new User_Limit('giveLifeDay');
 		if( $limit->getLastTimes() ){
 			if( $limit->getUsedTimes($to) ){
 				ret('同一好友每天只能赠送一次',-1);
 			}
	 		/*$mail = new User_Mail( $to );
	 		$life = $limit->getGiveNum();
	 		$con = ' 亲，我给你赠送了 '.$life.' 点体力，记得回赠我哦。 ';
	 		$goods = array('life'=>$life);
	 		$mail->sendMail($con, 2, $to, '收获体力', json_encode($goods), $user->getUserName(),get3unix());*/
	 		$friend = new User_Friend( $user->getUid(), $to );
	 		$friend->sendLife();

	 		$limit->addLimitTimes();
	 		$limit->addLimitTimes(1,$to);
	 		$give['mFriend'] = 5;
	 		ret( $user->sendGoodsFromConfig($give) );
	 	}else{
	 		ret( '今日次数已用完',-1 );
	 	}
	 case '61'://领取体力  getLife
 		$tag = '领取体力';
 		$to = $input['to'];
 		if( empty( $to ) ){
 			ret('YMD',-1);
 		}
 		if( $to == $user->getUid() ){
 			ret('YMD',-1);
 		}

 		$friend = new User_Friend( $user->getUid(), $to );
 		$life = $friend->receiveLife();
 		if( !empty( $life ) ){
	 		$give['life'] = $life;
	 		ret( $user->sendGoodsFromConfig($give) );
	 	}else{
			ret( '已领取', -1 );	 		
	 	}
 	case '7'://删除指定好友
 		$tag = '删除指定好友';
 		$to = isset( $input['to'] ) ? $input['to'] : 0 ;
 		if( empty( $to ) ){
 			ret( ' YMD_'.__LINE__,-1 );
 		}
 		$friend = new User_Friend( $user->getUid(),$to );
 		if( $friend->delFriend() ){
 			ret('suc');
 		}else{
 			ret( $friend->getErrorInfo(), -1 );
 		}
 	
 	case '8':# 好友佣兵列表
 		$ret = array();
 		$friend = new User_Friend();
 		$fList = $friend->getFriendList();
 		$uMerc = new User_Merc();
 		$hasMerc = $uMerc->getHadList();
 		$log->d( 'fList:'.json_encode($fList) );
 		foreach( $fList as $v ){
 			if(  !empty( $hasMerc ) && in_array( $v[0], $hasMerc ) ){
 				continue;
 			}
 			$merc = new User_Merc( $v[0] );
 			$fMerc = $merc->getMercHero();
 			if( !empty( $fMerc ) ){
 				$ret[ $v[0] ] = $fMerc;
 			}
 			unset($merc);
 		}
 		$sysUser = $uMerc->getSysMercHero();
 		$sysUserList = array();
 		foreach( $sysUser as $k=>$v ){
 			if( $k == $user->getUid() )continue;
 			if( in_array( $k, $hasMerc ) )continue;
 			if( $friend->isFriend( $k ) )continue;
 			if( count( $sysUserList ) > 4 ) break;
 			$sysUserList[$k] = $v;
 		}
 		ret( array('friend'=>$ret,'sys'=>$sysUserList) );
 	case '9':# 确认雇佣好友佣兵
 		$friendUid = $input['fid'];
 		$uMerc = new User_Merc();
 		$uMerc->addHadUid( $friendUid );
 		ret( $ret );
 	case '10': #查看好友英雄及装备
 		$fid = $input['fid'];
 		if( empty( $fid ) || !is_numeric( $fid ) ){ret('fid_error',-1);}
 		$friend = new User_Friend($user->getUid());
 		if( !$friend->isFriend( $fid ) ){ret('no_friend',-1);}
 		$hero = new User_Hero( $fid );
 		$heroList = $hero->getStrongHeroList();
 		$ret['hList'] = $heroList;
 		$ret['top'] = (int)$hero->getUserRecord('maxPvpTop');
 		ret( $ret );
 	default:
 		# code...
 		break;
 }
?>