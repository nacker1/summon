<?php
/**
*@ User_Friend 好友类
**/
class User_Friend extends User_Base{
	private $friendTable = 'zy_uniqRoleFriends';				//好友表
	private $toUid;								//接爱邀请的玩家uid
	private $toCond;							//接爱邀请玩家的初始化redis连接
	private $cond;								//当前用户的好友连接redis表
	private $errorInfo;							//操作错误信息
/**
 *@ 好基友类
 *@ param:
 *	$uid: 	用户uid
 *	$toUid:	目标用户uid
 **/
	function __construct( $uid='', $toUid='' ){
		parent::__construct( $uid );
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		$this->toUid = $toUid;
		$this->cond = new Cond( 'userFriendList',$this->getUid(),0,'Friend' );
		if( !empty( $this->toUid ) ){
			$this->toCond = new Cond( 'userFriendList',$this->toUid,0,'Friend' );
		}
	}
/**
 *@ 发送添加好友邀请
 **/
	function sendInvite(){
		if( !empty( $this->toUid ) ){
			$toCond = new Cond( 'userFriendOpt',$this->toUid,86400*15,'Friend' );
		}else{
			ret(' error_no_friendId_'.__LINE__ ,-1);
		}
		if( $this->cond->get( 'lists:'.$this->toUid ) ){
			$this->errorInfo = ' 玩家已经是你的好友 ';
			return false;
		}
		$toUser = new User_Base( $this->toUid );
		$toUser->setUserHeart('invite');
		$sendInfo['name'] = $this->getUserName();
		$sendInfo['uid'] = $this->getUid();
		$sendInfo['img'] = $this->getImage();
		$sendInfo['level'] = $this->getLevel();
		return $toCond->set( $sendInfo, $this->getUid() );
	}
/**
 *@ 拉取当前用户的好友邀请列表信息
 **/
	function getInviteLists(){
		$cond = new Cond( 'userFriendOpt',$this->getUid(),3600,'Friend' );
		return $cond->getAll();
	}
/**
 *@ 删除邀请列表
 **/	
	function delInvite(){
		$cond = new Cond( 'userFriendOpt',$this->getUid(),3600,'Friend' );
		$invite = $cond->get( $this->toUid );
		if( empty( $invite ) ){
			$this->errorInfo = ' 邀请不存在或已过期 ';
			return false;
		}
		return $cond->del( $this->toUid ); 
	}
/**
 *@ 同意好友邀请
 **/
	function agreeUserInvite(){
		// 第二步 判断用户以及对家好友数量是否已满
		$toUser = new User_User( $this->toUid, -1 );
		$toUserHaveFriends = (int)$this->toCond->get('tolFriends');
		if( $toUserHaveFriends >= $toUser->getMaxFriends() ){
			$this->errorInfo = ' 对方好友人数已满 ';
			return false;
		}

		$myFriends = (int)$this->cond->get('tolFriends');

		if( $myFriends >= $this->getMaxFriends() ){
			$this->errorInfo = ' 您的好友人数已达到上限 ';
			return false;
		}
		if( $this->delInvite() === false ){return false;}

		// 第三步 互相添加好友 
		//----------------------------------------- 对方加自己为好友 ---------------------------
		$myInfo['uid'] = $this->getUid();
		$this->toCond->set( $myInfo, 'lists:'.$this->getUid() );
		$this->toCond->add( 1,'tolFriends' );
		$this->toCond->del( 'listInfo' ); //删除好友缓存
		$this->setThrowSQL( $this->friendTable, array( 'uid'=>$this->toUid,'friendUid'=>$this->getUid(),'time'=>time() ) );
		//----------------------------------------- 自己加对方为好友 ---------------------------
		unset($myInfo);
		$myInfo['uid'] = $toUser->getUid();
		$this->cond->set( $myInfo, 'lists:'.$this->toUid );
		$this->cond->add( 1,'tolFriends' );
		$this->cond->del( 'listInfo' ); //删除好友缓存
		$this->setThrowSQL( $this->friendTable, array( 'uid'=>$this->getUid(),'friendUid'=>$this->toUid,'time'=>time() ) );
		return true;
	}
/**
 *@ 拉取好友列表
 **/
	public function getFriendList(){
		$fList = $this->cond->get( 'listInfo' );
		if( empty( $fList ) ){
			$friends = $this->cond->getAll( 'lists' );
			if( empty($friends) && !$this->cond->get( 'checked' ) ){ //从数据库同步
				$this->db;
				$ret = $this->db->find( $this->friendTable,'friendUid',array( 'uid'=>$this->getUid() ) );
				if( is_array( $ret ) ){
					$friends = array();
					foreach( $ret as $v ){
						$this->cond->set( array( 'uid'=>$v['friendUid'] ), 'lists:'.$v['friendUid'] );
						array_push( $friends, array('uid'=>$v['friendUid']) );
					}
				}

				$this->cond->set( 1, 'checked', 86400 * 15 );
			}
			if( is_array( $friends ) ){
				foreach( $friends as $v ){
					$user = new User_User( $v['uid'],-1 );
					$friend['uid'] = $user->getUid();
					$friend['name'] = $user->getUserName();
					$friend['img'] = $user->getImage();
					$friend['level'] = $user->getLevel();
					$fList[] = $friend;
				}
				$this->cond->set( $fList, 'listInfo', 3600 );
			}
		}
		return $fList;
	}
/**
 *@ 删除指定好友
 **/
	public function delFriend(){
		if( $this->cond->get( 'lists:'.$this->toUid ) ){
			$this->cond->del( 'lists:'.$this->toUid );
			$this->cond->del( 'listInfo' );
			$tol = (int)$this->cond->get( 'tolFriends' );
			if( $tol - 1 < 0 ){
				$tol = 0;
			}else{
				$tol -= 1;
			}
			$this->cond->set( $tol, 'tolFriends' );
			$this->toCond->del( 'lists:'.$this->uid );
			$this->toCond->del( 'listInfo' );
			$tol = (int)$this->toCond->get( 'tolFriends' );
			if( $tol - 1 < 0 ){
				$tol = 0;
			}else{
				$tol -= 1;
			}
			$this->toCond->set( $tol, 'tolFriends' );
			$this->setThrowSQL( $this->friendTable, '', array( 'uid'=>$this->getUid(),'friendUid'=>$this->toUid ) );
			$this->setThrowSQL( $this->friendTable, '',array( 'uid'=>$this->toUid,'friendUid'=>$this->getUid() ) );
			return true;
		}else{
			$this->errorInfo = ' 好友关系已经解除 ';
			return false;
		}
	}
/**
 *@ isFriend 用于判断用户是否为当前用户的好友
 **/
	public function isFriend( $uid ){
		$friends = $this->getFriendList();
		dump($friends);
		foreach( $friends as $v ){
			if( $uid == $v['uid'] )return true;
		}
		return false;
	}
/**
 *@ getErrorInfo 获取错误信息
 **/
	public function getErrorInfo(){
		return $this->errorInfo;
	}
}
?>