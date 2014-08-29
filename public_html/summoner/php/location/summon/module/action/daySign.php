<?php
/**
 *@ 每日签到接口
 **/

 $user = new User_User();

 $type = isset( $input['t'] ) ? $input['t'] : 1;
 $month = isset( $input['m'] ) ? $input['m'] : 1;

 $sign = new Act_Sign(  );

 switch ($type) {
 	case '1'://拉取每日签到配置信息
 		$tag = '签到配置';
 		ret( $sign->getSignConfig( $month ) );
 		break;
 	case '2'://签到
 		$tag = '签到领取';
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