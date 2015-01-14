<?php
/**
 *@ Chat 聊天系统类
 **/
 class Chat extends User_Base{
 	private $cond;				#世界信息redis连接源
 	private $uCond;				#玩家私信redis连接源
 	private $type;				#信息类型  1为世界信息  2为私信  3为聊天公告 5为公会信息

 	function __construct( $uid='', $type=1, $time=0 ){
 		# $uid: 如果是发送信息则为接收者的uid, 如果是拉信息则为当前用户的uid
 		parent::__construct( $uid );
 		if( $time < 1 ){
 			$time = CHAT_DEFAULT_TIMES;
 		}
 		$this->cond = new Cond( 'chat', 0, $time );
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
 *@ sendChat() 发布聊天信息
 * param:
 *	$con:	 发送的内容
 *	$name:	 发信者信息
 *	$uid:	 发信者的uid 可用来直接加好友
 *	$level:	 发信者等级
 *	$image:	 发信者的头像id
 *  $other: 其它信息，如pvp战斗记录，挖矿记录等，数据结构
 *		type|key|showCon   类型|唯一键(fromUid,toUid)|显示内容
 *		pvp => type:1, key:pvp生成， showCon: 显示在页面上的内容
 **/
 	function sendChat( $con, $name='公告', $uid='', $level=0, $image=0, $other='' ){
 		$chat = array(
 			'con'=>$con,
 			'name'=>$name,
 			'level'=>$level,
 			'image'=>$image,
 			'uid'=>$uid,
 			'time'=>time(),
 			'to'=>'',
 			'other'=>$this->praseOther($other)
 		);
 		return $this->_setChat( $chat );
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
	 				$temp[] = $v['to'];
	 				$temp[] = $v['other'];
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
	 				$temp[] = $v['to'];
	 				$temp[] = $v['other'];
	 				$ret[] = $temp;
	 				unset($temp);
	 			}
	 		}
 		return $ret;
 	}
/**
 *@ praseOther 根据业务解析other信息内容
 **/
 	private function praseOther( $other ){
		if( empty( $other ) )return $other;
		
		$ot = explode('|',$other);
		$ret = $ot[0];
		switch ($ot[0]) {
			case 'pvp':  #分享pvp
				if( isset( $ot[1] ) ){
					$info = explode( ',', $ot[1] );
					$ret .= '|'.$info[0].','.$info[1].','.$info[2].'|【'.$info[3].' VS '.$info[4].'】';
		 		}
				break;
			default:
				# code...
				break;
		}
		return $ret;
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