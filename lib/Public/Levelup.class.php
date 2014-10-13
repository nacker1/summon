<?php
/**
 *@ 角色升级信息表
 **/
 class Levelup extends Pbase{
	private $role_table='zy_baseRoleLevelUp';
	private $hero_table='zy_baseHeroLevelUp';
	private $maxLevel=80;					//最大等级
	private $level;						//等级
	private $type;						//升级功能块  user:为召唤师升级  hero:英雄升级
	private $now;						//当前等级对应信息
	private $next;						//下一等级对应信息

	public function __construct( $level,$type='user' ){
		parent::__construct();
		$this->level = $level;
		$this->type = $type;
		$this->_init();
	}

	private function _init(){ //初始化角色升级信息
		if( $this->type == 'user' ){
			if( C('test') || !$this->pre->exists('roleLevelUp_check') ){
				$this->cdb;
				$upinfo = $this->cdb->find($this->role_table,'*');
				foreach( $upinfo as $v ){
					$this->pre->hmset('roleLevelUp:'.$v['level'],$v);
				}
				$this->pre->set( 'roleLevelUp_check',1,get3time() );
			}
			$this->now = $this->pre->hgetall('roleLevelUp:'.$this->level);
			$this->next = $this->pre->hgetall('roleLevelUp:'.( $this->level+1 ) );
		}elseif( $this->type == 'hero' ){
			if( C('test') || !$this->pre->hexists('heroLevelUp:*') ){
				$this->cdb;
				$upinfo = $this->cdb->find($this->hero_table,'*');
				foreach( $upinfo as $v ){
					$this->pre->hmset('heroLevelUp:'.$v['level'],$v);
				}
			}
			$this->now = $this->pre->hgetall('heroLevelUp:'.$this->level);
			$this->next = $this->pre->hgetall('heroLevelUp:'.( $this->level+1 ) );
		}

		if( empty( $this->next ) ){
			$this->next = $this->now;
		}
	}

	public function getUpinfo(){
		return $this->now;
	}

	public function getNextUpinfo(){
		return $this->next;
	}

	function getMaxLevel(){
		return $this->maxLevel;
	}
 }
?>
