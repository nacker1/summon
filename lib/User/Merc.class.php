<?php
/**
 *@ 佣兵类
 **/
class User_Merc extends User_Base{

	function __construct( $uid='' ){
		parent::__construct($uid);
		$this->cond = new Cond( 'merc', '', 0 , 'Friend');
	}
/**
 *@ 获取用户已经雇佣过的好友id
 **/
	function getHadList(){
		return $this->cond->get('hadList:'.$this->uid);
	}
/**
 *@ 添加用户已经雇佣过的好友id
 **/
	function addHadUid( $friendUid ){
		return $this->cond->add( $friendUid, 'hadList:'.$this->uid);
	}
/**
 *@ 获取用户可以提供的佣兵信息
 **/
	function getMercHero(){
		return $this->cond->get('mercHero:'.$this->uid);
	}
/**
 *@ 设置用户提供的佣兵信息
 **/
	function setMercHero( $heroInfo ){
		return $this->cond->set( $heroInfo, 'mercHero:'.$this->uid );
	}
}
?>