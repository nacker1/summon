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
	 		$mail = new User_Mail();
	 		$life = $limit->getGiveNum();
	 		$con = ' 亲，我给你赠送了 '.$life.' 点体力，记得回赠我哦。 ';
	 		$goods = array('life'=>$life);
	 		$mail->sendMail($con, 2, $to, '收获体力', json_encode($goods), $user->getUserName());
	 		$limit->addLimitTimes();
	 		$limit->addLimitTimes(1,$to);
	 		ret('suc');
	 	}else{
	 		ret( '今日次数已用完',-1 );
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
 		foreach( $fList as $v ){
 			if(  !empty( $hasMerc ) && in_array( $v['uid'], $hasMerc ) ){
 				continue;
 			}
 			$merc = new User_Merc( $v['uid'] );
 			$fMerc = $merc->getMercHero();
 			if( !empty( $fMerc ) ){
 				$ret[] = $fMerc;
 			}
 			unset($merc);
 		}
 		ret( array('list'=>$ret) );
 	case '9':# 确认雇佣好友佣兵
 		$friendUid = $input['fid'];
 		$uMerc = new User_Merc();
 		$uMerc->addHadUid( $friendUid );
 		ret( $ret );
 	default:
 		# code...
 		break;
 }
?>