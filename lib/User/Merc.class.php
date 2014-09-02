<?php
/**
 *@ 佣兵类
 **/
class User_Merc extends User_Base{
	static private $cond;				//佣兵公共限制连接类

	function __construct( $uid='' ){
		parent::__construct($uid);
		if( empty( self::$cond ) )
			self::$cond = new Cond( 'merc', '', 0 , 'Friend');
	}
/**
 *@ 获取用户已经雇佣过的好友id
 **/
	function getHadList(){
		return self::$cond->get('hadList:'.$this->uid);
	}
/**
 *@ 添加用户已经雇佣过的好友id
 **/
	function addHadUid( $friendUid ){
		return self::$cond->add( $friendUid, 'hadList:'.$this->uid);
	}
/**
 *@ 获取用户可以提供的佣兵信息
 **/
	function getMercHero(){
		return self::$cond->get('mercHero:'.$this->uid);
	}
/**
 *@ 设置用户提供的佣兵信息
 **/
	function setMercHero( $heroInfo ){
		$sysListString = self::$cond->get( 'sysTempUser' );
		$sysList = explode( '#', trim( $sysListString,'#' ) );
		if( empty( $sysList ) || count( $sysList ) < 86 ){
			empty( $sysListString ) && $sysListString='';
			if( !in_array( $this->uid, $sysList ) )
				self::$cond->set( $sysListString.'#'.$this->uid, 'sysTempUser' );
		}
		return self::$cond->set( $heroInfo, 'mercHero:'.$this->uid );
	}
/**
 *@ 获取系统佣兵
 **/
	function getSysMercHero(){
		$ret = array();
		$sysHero = self::$cond->get( 'sysTempUser' );
		$sysHero = explode( '#', trim( $sysHero,'#' ) );
		foreach( $sysHero as $v ){
			$ret[$v] = self::$cond->get( 'mercHero:'.$v );
		}
		return $ret;
	}
}
?>