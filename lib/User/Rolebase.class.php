<?php
/**
 *@ 角色基本信息类
 **/
class User_Rolebase{
	protected $redis; 						//角色所在redis服务器初始化连接
	protected $rid; 						//用户在登录服务上的id
	protected $flag=10;						//登录服务器redis配置数量
	
	public function __construct( $rid ){
		$this->rid = $rid;
		$tag = $rid % $this->flag;
		$this->redis = Redis_Redis::init( 'Login_'.$tag );
	}
   /**
	*@ 返回用户角色id
	**/
	public function getRid(){
		return $this->rid;
	}
	/**
	 *@ 获取用户最后登录的服务器id
	 **/
	public function getUserLastServerId(){
		$ret = $this->redis->get('lastServer:'.$this->rid);
		return $ret ? $ret : 0;
	}

	/**
	 *@ 设置用户最后登录的服务器id
	 **/
	public function setUserLastServerId( $sid ){
		$ret = $this->redis->set('lastServer:'.$this->rid,$sid);
		return $ret ? 1 : 0;
	}
}
?>