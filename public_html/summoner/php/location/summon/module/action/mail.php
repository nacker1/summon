<?php
/**
 *@ 邮箱接口
 **/
 $user = new User_User();
 
 $type = isset( $input['t']) ? $input['t'] : 1;
 
 $type = empty( $type ) ? 1 : $type;
 


 //$mail->sendMail('测试一下吧',2,381440,'公告活动领取奖品',json_encode(array('money'=>100,'good'=>'63001,1#63002,3#30001,2')),'小鸡鸡');

 switch( $type ){
	case '1': //读取邮件列表
		$mail = new User_Mail( $user->getUid() );
		$tag = '拉取邮件列表';
		$retMail['list'] = $mail->getEmailList();
		ret( $retMail );
	case '2': //邮件操作 操作完后删除邮件
		$mailType = isset( $input['mt']) ? $input['mt'] : 1;
 		$mail = new User_Mail( $user->getUid(), $mailType );
		$tag = '领取邮件奖励';
		$key = isset( $input['key']) ? $input['key'] : 0;
		if( empty( $key ) ){
			ret('Key_Error!Code:'.__LINE__,-1);
		}
		
		if( $mailType == 1 ){#领取私人邮件
			$goods = $mail->getMailGoodsByKey( $key );
			$mail->delMail( $key );
		}elseif( $mailType == 2 ){#领取公共邮件
			if($mail->isSend( $key )){#系统检测用户是否已领取
				ret( ' 不能重复领取 ', -1 );
			}
			$goods = $mail->getPubMailGoodsByKey( $key );
			if( !MAIL_REGET ) $mail->setSend( $key );
		}
		if( empty( $goods ) ){
			#$mail->delMail( $key );
			$log->e(  '* 用户#'.$user->getUid().'#邮箱物品领取失败，物品配置为空，所传key值：'.$key );
			ret('Key_Error!Code:'.__LINE__,-1);
		}
		$log->i( '* 用户#'.$user->getUid().'#邮箱物品领取配置信息'.json_encode($goods) );
		$ret = $user->sendGoodsFromConfig( $goods );
		
		ret( $ret );
	default:
		ret('Type_ERROR!',-1);
 }
?>