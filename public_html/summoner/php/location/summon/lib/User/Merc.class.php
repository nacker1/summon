<?php
/**
 *@ 佣兵类
 **/
class User_Merc extends User_Base{

	function __construct( $uid='' ){
		parent::__construct($uid);
		$this->cond = new Cond( 'merc', $this->uid, 0 , 'Friend');
	}
/**
 *@ 获取用户已经雇佣过的好友id
 **/
	function getHadList(){
		$hList = $this->cond->get('hadList');
	}
/**
 *@ 添加用户已经雇佣过的好友id
 **/
	function addHadUid( $friendUid ){
		$hList = $this->cond->add( $friendUid, 'hadList');
	}
/**
 *@ 获取用户可以提供的佣兵信息
 **/
	function getMercHero(){
		$hList = $this->cond->get('mercHero');
	}
/**
 *@ 设置用户提供的佣兵信息
 **/
	function setMercHero( $heroInfo ){
		$hList = $this->cond->set($heroInfo, 'mercHero');
	}
}
?>