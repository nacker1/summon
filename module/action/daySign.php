<?php
/**
 *@ 每日签到接口
 **/

 $user = new User_User();

 $type = isset( $input['t'] ) ? $input['t'] : 1;
 
 switch ($type) {
 	case '1'://拉取每日签到配置信息
 		$tag = '签到配置';
 		$sign = new Act_Sign( $user->getUid() );
 		$month = isset( $input['m'] ) ? $input['m'] : 1;
 		ret( $sign->getSignConfig( $month ) );
 		break;
 	case '2'://签到
 		$tag = '签到领取';
 		$sign = new Act_Sign( $user->getUid() );
 		$add = $sign->signIn();
 		if( $add === false ){
 			ret('今日已领',-1);
 		}
 		$ret = $user->sendGoodsFromConfig( $add );
 		$ret['tol'] = $sign->getTotalTimes();
		$ret['com']= $sign->getCommonTimes();
		$ret['vip'] = $sign->getVipTimes();
		
 		ret( $ret );
 		break;
 	case '10': #黄金矿山领取奖励
 		$tag = '黄金矿山领取奖励';
 		$limit = new User_Limit( $user->getUid(), 'goldMineDay' );
 		if( $limit->getLastTimes() < 1 ){  #领取次数已用完
 			ret( '黄金矿山任务已完成', -1 );
 		}
 		if( $limit->getTimeLimit() ){   #两次领取冷却时间
 			ret( '领取时间未到',-1 );
 		}
 		$times = $limit->getUsedTimes();
 		$gold = new Gold( $times+1 );
 		if( $times < 1 && ( time() - $user->getUserField('fLoginTime') ) < $gold->getTime()  ){  #登录时间少于第一次规定的时间
 			ret( '领取时间未到'.$gold->getTime(),-1 );
 		}
 		$user->setMissionId( 2, 66 );
 		$limit->addLimitTimes(1);
 		$limit->setTimeLimit('',$gold->getNextTime());
 		$ret = $user->sendGoodsFromConfig( $gold->getReward() );
 		
 		ret( $ret );
 	case '999':
 		#清除签到相关数据
 		$sign->delCache();
 		ret('数据清除成功');
 		break;
 	default:
 		# code...
 		break;
 }
ret( '看~~  灰机~~', -1 );
?>