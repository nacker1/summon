<?php
/**
 *@ Chat 聊天系统类
 **/
 class Chat extends Base{
 	private $cond;				#世界信息redis连接源
 	private $uCond;				#玩家私信redis连接源
 	private $type;				#信息类型  1为世界信息  3为私信  5为公会信息

 	function __construct( $uid='' ){
 		dump($_SERVER);exit;
 		# $uid: 如果是发送信息则为接收者的uid, 如果是拉信息则为当前用户的uid
 		parent::__construct( $uid );
 		$this->cond = new Cond( 'chat', 0, 600 );
 		$this->type = 1;
 		if( !empty( $this->uid ) ){
 			$this->type = 3;
 			$this->uCond = new Cond( 'chat', $this->uid, 86400 );	#私信保存一天
 		}
 	}
/**
 *@ 获取所有聊天信息 包括世界信息与私信
 * param:
 *	$lasttime: 客户端收到的最后一条信息时间戳
 **/
 	function getChat( $lasttime ){
 		$ret['p'] = $this->_getWorldChat( $lasttime );
 		$ret['u'] = $this->_getUserChat( $lasttime );
 		return $ret;
 	}
/**
 *@ _getUserChat 获取玩家的私信 
 * param:
 *	$lasttime: 客户端收到的最后一条信息时间戳
 **/
 	private function _getUserChat( $lasttime ){
 		$cList = $this->uCond->getAll();
 		foreach( $cList as $v ){
 			if( $v['time'] > $lasttime ){
 				$ret[] = $v;
 			}
 		}
 		return $ret;
 	}
 /**
 *@ _getWorldChat 获取世界信息
 * param:
 *	$lasttime: 客户端收到的最后一条信息时间戳
 **/
 	private function _getWorldChat( $lasttime ){
 		$cList = $this->cond->getAll();
 		foreach( $cList as $v ){
 			if( $v['time'] > $lasttime ){
 				$ret[] = $v;
 			}
 		}
 		return $ret;
 	}
/**
 *@ sendChat() 发布聊天信息
 * param:
 *	$con:	 发送的内容
 *	$name:	 发信者信息
 *	$uid:	 发信者的uid 可用来直接加好友
 **/
 	function sendChat( $con, $name, $uid ){
 		$chat = array(
 			'con'=>$content,
 			'name'=>$name,
 			'type'=>$this->type,
 			'uid'=>$uid,
 			'time'=>time()
 		);
 		return $this->_setChat( $chat );
 	}

 	private function _setChat( $con ){
 		switch( $this->type ){
 			case '3' #发私信
 				return $this->uCond->set( $con );
 			default:
 				return $this->cond->set( $con );
 		}
 	}
 }
?>