<?php
/**
 *@ Chat 聊天系统类
 **/
 class Chat extends User_Base{
 	private $cond;				#世界信息redis连接源
 	private $uCond;				#玩家私信redis连接源
 	private $type;				#信息类型  1为世界信息  2为私信  5为公会信息

 	function __construct( $uid='', $type=1 ){
 		# $uid: 如果是发送信息则为接收者的uid, 如果是拉信息则为当前用户的uid
 		parent::__construct( $uid );
 		$this->cond = new Cond( 'chat', 0, 600 );
 		$this->type = $type;
		$this->uCond = new Cond( 'chat', $this->uid, 86400 );	#私信保存一天
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
 		$this->setMessageFlag(0);
 		$ret=array();
 		$cList = $this->uCond->getAll();
 		if( is_array( $cList ) )
	 		foreach( $cList as $v ){
	 			if( $v['time'] > $lasttime ){
	 				$temp[] = $v['con'];
	 				$temp[] = $v['name'];
	 				$temp[] = $v['level'];
	 				$temp[] = $v['image'];
	 				$temp[] = $v['uid'];
	 				$temp[] = $v['time'];
	 				$ret[] = $temp;
	 				unset($temp);
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
 		$ret=array();
 		$cList = $this->cond->getAll();
 		if( is_array( $cList ) )
	 		foreach( $cList as $v ){
	 			if( $v['time'] > $lasttime ){
	 				$temp[] = $v['con'];
	 				$temp[] = $v['name'];
	 				$temp[] = $v['level'];
	 				$temp[] = $v['image'];
	 				$temp[] = $v['uid'];
	 				$temp[] = $v['time'];
	 				$ret[] = $temp;
	 				unset($temp);
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
 *	$level:	 发信者等级
 *	$image:	 发信者的头像id
 **/
 	function sendChat( $con, $name, $uid, $level, $image ){
 		$chat = array(
 			'con'=>$con,
 			'name'=>$name,
 			'level'=>$level,
 			'image'=>$image,
 			'uid'=>$uid,
 			'time'=>time()
 		);
 		return $this->_setChat( $chat );
 	}

 	private function _setChat( $con ){
 		switch( $this->type ){
 			case '2': #发私信
 				$uniq = uniqid(true);
 				$this->uCond->set( $con, $uniq );
 				$this->setMessageFlag(1);
 				$mCond = new Cond( 'chat', $con['uid'], 86400 );	#私信保存一天
 				$uInfo['name'] = $this->getUserName();
 				$uInfo['image'] = $this->getImage();
 				$uInfo['level'] = $this->getLevel();
 				$uInfo['uid'] = $this->getUid();
 				$con['to'] = json_encode($uInfo);
 				$mCond->set( $con, $uniq );
 				return true;
 			default:
 				return $this->cond->set( $con, uniqid(true) );
 		}
 	}
 }
?>