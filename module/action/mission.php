<?php
/**
 *@ 用户系统接口
 **/
 $user = new User_User();

 $type = isset( $input['t'] ) ? $input['t'] : 0;

 if( empty( $type ) ){
 	ret('YMD',-1);
 }
 
 $mType = isset( $input['mt'] ) ? $input['mt'] : 0;

 if( empty($mType) ){
 	ret( "YMD",-1 );
 }

 $mission = new User_Mission($mType);

 switch ($type) {
 	case '1': //拉取当前自己的任务列表
 		$tag = '拉取任务列表';
 		ret( $mission->getMissionList() );
 		break;
 	case '2': //领取任务奖励
 		$tag = '领取任务奖励';
 		$taskId = isset( $input['tid'] ) ? $input['tid'] : 0 ;
 		if( empty($taskId) ){ret('YMD',-1);}
 		$goodsConfig = $mission->getMissionGoods( $taskId );
 		if( $goodsConfig == false ){
 			ret( $mission->getErrorInfo(),-1 );
 		}else{
 			ret( $user->sendGoodsFromConfig( $goodsConfig ) );
 		}
 	default:
 		break;
 }

?>