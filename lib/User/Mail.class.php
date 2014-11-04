<?php
/**
 *@ 用户邮件类
 **/
 class User_Mail extends User_Base{
	private $mailRedis;		//邮件连接的redis服务器
	private $pubRedis;		//公共邮件连接redis服务器
	function __construct( $uid='', $mType=0 ){
		parent::__construct( $uid );
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		switch( $mType ){
			case '1':
				$this->mailRedis = new Cond( 'userMail',$uid );break;
			case '2':
				$this->mailCheck = new Cond( 'publicMail_check', $this->uid );
				$this->pubRedis = new Cond( 'publicMail' );break;
			default:
				$this->pubRedis = new Cond( 'publicMail' );
				$this->mailRedis = new Cond( 'userMail',$uid );
		}
	}
		
/**
 *@ getEmailList 获取用户邮件列表
 **/
	function getEmailList(){
		$this->setNewMail(0);
		$priMail = $this->mailRedis->getAll();
		$pubMail = $this->pubRedis->getAll();
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
 *	$to:		收件人uid 如果所有人都可用则 to=0
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
		$send['key'] = $uniqKey;										#邮件唯一标识
		$send['type'] = (int)$type;										#邮件类型（1文字类公告， 2为领取类）
		$send['tit'] = $tit;											#邮件标题
		$send['con'] = $con;											#邮件内容
		$send['sendTime'] = time();										#发送时间	
		$send['sendUser'] = $sendUser;									#发送用户名
		$send['mType'] = !empty( $to ) && (int)$to>0 ? 1 : 2; 			#邮件类型  1为私人邮件， 2为公共邮件
		if( $to<1 ){ //to 接收邮件的用户uid  如果to<1则为所有用户
			$mailRedis = $this->pubRedis;#new Cond('publicMail','',$time);
		}else{
			$mailRedis = $this->mailRedis;#new Cond('userMail',$to,$time);
			$toUser = new User_User( $to,-1 );
			$toUser->setNewMail(1); //标记有新邮件  心跳中提示
		}
		$this->log->e('mail_info:'.json_encode($send));
		return $mailRedis->set($send,$uniqKey,$time);
	}
/**
 *@ 获取私人邮件的奖品信息
 **/
	function getMailGoodsByKey( $key ){
		$mail = $this->getMailByKey($key);
		if( empty( $mail ) || empty( $mail['goods'] ) )return false;
		return $mail['goods'];
	}

	function getMailByKey( $key ){
		$mail = $this->mailRedis->get( $key );
		$this->log->e( 'mailConfig:'.json_encode($mail) );
		return $mail;
	}

/**
 *@ 获取公共邮件的奖品信息
 **/
	function getPubMailGoodsByKey($key){
		$pMail = $this->getPubMailByKey( $key );
		return $pMail['goods'];
	}

	function getPubMailByKey( $key ){
		$pubMail = $this->pubRedis->get( $key );
		$this->log->e( 'pubMailConfig:'.json_encode($pubMail) );
		return $pubMail;
	}

	function isSend( $key ){ #用户公共邮件  查询用户是否已经领取过奖励
		return $this->mailCheck->get( $key );
	}

	function setSend( $key ){ #用户公共邮件  设置用户是否已经领取过奖励
		$time = $this->pubRedis->ttl( $key );
		dump($time);exit;
		return $this->mailCheck->set( 1, $key, $time );
	}

	function delMail( $key ){
		return $this->mailRedis->del( $key );
	}
 }
?>