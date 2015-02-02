<?php
/**
 *@ 佣兵类
 **/
class User_Merc extends User_Base{
	static private $cond;				//佣兵公共限制连接类

	function __construct( $uid='' ){
		parent::__construct($uid);
		$this->log->d('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		if( empty( self::$cond ) )
			self::$cond = new Cond( 'merc', '', get3time() , 'Friend');
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
		return self::$cond->add( array($friendUid), 'hadList:'.$this->uid);
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
		if( empty( $sysList ) || count( $sysList ) < 40 ){
			empty( $sysListString ) && $sysListString='';
			if( !in_array( $this->uid, $sysList ) )
				self::$cond->set( $sysListString.'#'.$this->uid, 'sysTempUser', get3time() );
		}
		return self::$cond->set( $heroInfo, 'mercHero:'.$this->uid, 86400*7 );
	}
/**
 *@ 获取系统佣兵
 **/
	function getSysMercHero(){
		$ret = array();
		$sysHero = self::$cond->get( 'sysTempUser' );
		$sysHero = explode( '#', trim( $sysHero,'#' ) );
		$uLevel = $this->getLevel();
		foreach( $sysHero as $v ){
			#if( $v['level'] >= $uLevel && $v['level'] <= ( $uLevel+10 ) )
			$temp = self::$cond->get( 'mercHero:'.$v );
			if( $temp['level'] >= $uLevel && $temp['level'] <= ( $uLevel+10 ) ){
				$ret[$v] = $temp;
			}
			$this->log->d( 'mercs:'.json_encode( $temp ) );
		}
		return $ret;
	}
}
?>