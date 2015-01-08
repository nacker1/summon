<?php
/**
 *@ Act_Code 兑换码通用类
 **/
 class Act_Code extends User_Base{
 	private $table='zy_actionCdkey';	//兑换码表
	private $code;				//兑换码 	
	private $errInfo='兑换成功';		//兑换码错误信息
	private $cond;

	public function __construct( $code ){
		parent::__construct();
		$this->code = $code;
		$this->cond = new Cond( $this->table, $code );
	}
/**
 *@ getExchangeInfo() 获取兑换结果信息
 **/
	function  getExchangeInfo(){
		$this->cond->set( $this->errInfo );
		return $this->errInfo;
	}

/**
 *@ 获取兑换码对应的奖品配置信息
 **/
	public function getConfig(){
		if( $error = $this->cond->get() ){
			$this->errInfo = $error;
			return false;
		}
		$this->cdb;
		$this->log->d( '~~~~~~~~~~~~~~~~~~~~~~ SELECT DB ~~~~~~~~~~~~~~~~~~~~~~~' );
		$keyConfig = $this->cdb->findOne( $this->table,'*',array( 'cdkey'=>$this->code ) );
		if( empty( $keyConfig ) ){
			$this->log->e(' 用户#'.$this->uid.'#使用兑换码#'.$this->code.'#无效，兑换结束');
			$this->errInfo = ' 无效兑换码 ';
			return false;
		}
		if( $keyConfig['status'] > 0 ) {
			$this->log->e(' 用户#'.$this->uid.'#使用的兑换码#'.$this->code.'#已经被（'.$keyConfig['userName'].'）使用，兑换结束');
			$this->errInfo = ' 兑换码已被（'.$keyConfig['userName'].'）使用 ';
			return false;
		}
		if( $keyConfig['overTime'] < time() ) {
			$this->log->e(' 用户#'.$this->uid.'#使用兑换码#'.$this->code.'#已过期，兑换结束');
			$this->errInfo = ' 兑换码已过期 ';
			return false;
		}		
		$this->setCodeUsed();
		return $keyConfig['goods'];
	}
/**
 *@ setCodeUsed() ; 设置兑换码已被使用
 **/
	function setCodeUsed(){
		$upd['uid'] = $this->uid;
		$upd['userName'] = $this->getUserName();
		$upd['status'] = 1;
		$upd['useTime'] = time();
		$upd['serverId'] = $this->getServerId();
		$upd['rid'] = $this->getRid();
		$this->setThrowSQL($this->table,$upd,array('cdkey'=>$this->code),'','config');
	}
}
?>