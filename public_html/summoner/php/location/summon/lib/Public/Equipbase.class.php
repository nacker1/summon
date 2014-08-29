<?php
/**
 *@ 装备基础类
 **/
 class Equipbase extends Pbase{
	private $table='zy_baseEquip';	//基础装备表名
	private $eid;						//装备id
	private $eInfo;						//装备信息

	function __construct($eid=''){
		parent::__construct();
		$this->eid = $eid;
		$this->_init();
	}

	private function _init(){
		if( C('test') || !$this->pre->exists('equip:baseinfo_check') ){
			$this->cdb;
			$ret = $this->cdb->find($this->table);
			foreach( $ret as $v ){
				$this->pre->hmset( 'equip:baseinfo:'.$v['Equip_Id'],$v );
			}
			$this->pre->set('equip:baseinfo_check',1);
			$this->pre->expireat( 'equip:baseinfo_check',( mktime(23,59,59)+1800 ) );
		}

		if( !empty($this->eid) ){
			$this->eInfo = $this->pre->hgetall( 'equip:baseinfo:'.$this->eid );
		}
	}
/**
 *@ 装备需要的英雄最低等级
 **/
	function getEquipMinLevel(){
		return $this->eInfo['Hero_Level'];
	}
/**
 *@ 装备的最大强化次数
 **/
	function getEquipMax(){
		return $this->eInfo['Hero_Level'];
	}
 }
?>