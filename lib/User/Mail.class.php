<?php
/**
 *@ 用户邮件类
 **/
 class User_Mail extends User_Base{
	private $mailRedis;		//邮件连接的redis服务器
	function __construct( $uid='' ){
		parent::__construct( $uid );
		$this->mailRedis = new Cond( 'userMail',$uid );
	}
/**
 *@ getEmailList 获取用户邮件列表
 **/
	function getEmailList(){
		$this->setNewMail(0);
		$priMail = $this->mailRedis->getAll();
		$publicMail = new Cond( 'publicMail' ); //公共邮件
		$pubMail = $publicMail->getAll();
		$mList = array();
		if( !empty( $priMail ) )
			$mList = array_merge( $mList, $priMail );
		if( !empty( $pubMail ) )
			$mList = array_merge( $mList, $pubMail );
		return $mList;
	}
/**
 *@ sendMail 用户发送邮件
 *@ param:
 *	$con:		信件内容
 *	$type:		信件类型  1为公告 2为领取类
 *	$to:		收件人uid 公告 to=0
 *	$tit:		信件标题
 *	$time:		信件过期时间 过期时间戳
 *	$goods:	如果type=2时 goods必须格式{"life":2,"money":"100","good":"10030,1#63003,10"}
 **/
	function sendMail( $con,$type=1,$to=0,$tit='系统公告',$goods='',$sendUser='客服阿轿',$time='' ){
		if( empty( $time ) ){
			$time = 2592000; //默认保存一个月
		}else{
			$time = $time - time();
		}
		$uniqKey = uniqid();
		if( 2 == $type ){
			if( !empty( $goods ) ){
				$send['goods'] = $goods;
			}else{
				$this->log->e( '  发送邮件时配置类型为领取类，但是没有配置物品，邮件发送终止。 ' );
				return false;
			}
		}
		$send['key'] = $uniqKey;
		$send['type'] = (int)$type;
		$send['tit'] = $tit;
		$send['con'] = $con;
		$send['sendTime'] = time();
		$send['sendUser'] = $sendUser;
		if( $to<1 ){ //to 接收邮件的用户uid  如果to<1则为所有用户
			$mailRedis = new Cond('publicMail','',$time);
		}else{
			$mailRedis = new Cond('userMail',$to,$time);
			$toUser = new User_Base( $to );
			$toUser->setNewMail(); //标记有新邮件  心跳中提示
		}
		return $mailRedis->set($send,$uniqKey);
	}

	function getMailGoodsByKey( $key ){
		$mail = $this->mailRedis->get( $key );
		if( empty( $mail ) || empty( $mail['goods'] ) )return false;
		return $mail['goods'];
	}

	function delMail( $key ){
		return $this->mailRedis->del( $key );
	}
 }
?>