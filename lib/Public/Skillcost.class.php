<?php
/**
 *@ 技能等级升级消耗基础类  对就 zy_baseHeroSkillCost
 **/
 class Skillcost extends Pbase{
	private $table = 'zy_baseHeroSkillUp'; //技能升级表
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
				$this->pre->hmset( 'heroSkillCost:list:'.$v['Skill_Level'],$v );
				if( $v['Skill_Level'] == $this->level ){
					$this->sInfo = $v;
				}
			}
			$this->pre->set( 'heroSkillCost:checked',1,get3time() );
		}else{
			$this->sInfo = $this->pre->hgetall( 'heroSkillCost:list:'.$this->level );
		}
	}
	/**
	 *@ 获取当前等级升级需要消耗的金币数
	 **/
	public function getCostMoney(){
		$ret = $this->sInfo[ 'Skill'.$this->sIndex.'_Cose' ];
		return  empty($ret) ? 0 : $ret;
	}

 }
?>