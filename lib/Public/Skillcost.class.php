<?php
/**
 *@ 技能等级升级消耗基础类  对就 zy_baseHeroSkillCost
 **/
 class Skillcost extends Pbase{
	private $table = 'zy_baseHeroSkillCost'; //技能升级表
	private $sIndex;	//第几个技能
	private $level;		//技能等级
	private $sInfo;		//技能信息

	function __construct( $sIndex,$level ){
		parent::__construct();
		$this->sIndex = $sIndex;
		$this->level = $level;
		if( empty( $this->sIndex ) || empty( $this->level ) ){
			ret( 'faile', -1 );
		}
		$this->_init();
	}

	private function _init(){
		if( C('test') || !$this->pre->exists('heroSkillCost:checked') ){
			$this->cdb;
			$skills = $this->cdb->find( $this->table );
			foreach( $skills as $v ){
				$this->pre->hmset( 'heroSkillCost:list:'.$v['id'],$v );
				if( $v['id'] == $this->level ){
					$this->sInfo = $v;
				}
			}
			$this->pre->expire( 'heroSkillCost:checked',86400 );
		}else{
			$this->sInfo = $this->pre->hgetall( 'heroSkillCost:list:'.$this->level );
		}
	}
	/**
	 *@ 获取当前等级升级需要消耗的金币数
	 **/
	public function getCostMoney(){
		$ret = $this->sInfo[ 'skill'.$this->sIndex ];
		return  empty($ret) ? 0 : $ret;
	}

 }
?>