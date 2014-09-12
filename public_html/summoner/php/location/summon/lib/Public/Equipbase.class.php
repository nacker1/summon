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
			if( empty( $this->eInfo ) ){
				ret('no_eid_config',-1);
			}
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
/**
 *@ 计算装备战斗力
 *	装备战斗力 =（装备物理攻击 + 装备法术强度 + 装备物理护甲 + 装备魔法抗性）+
 *（装备生命值 +装备 生命回复 * 2 + 装备法力值 + 装备法力回复 * 2 + 装备移动速度）/ 10 +
 *（|装备攻击速度| + |装备普攻暴击| + |装备技能暴击| +  |装备普攻命中| + |装备技能命中| + |装备闪避| + 
 *	|装备护甲穿透| + |装备法术穿透| + |装备物理吸血| + |装备法术吸血| + |装备技能冷却|）* 100
 **/
	function getFire( $level=0 ){
		$att = $this->eInfo['Equip_Att'] + $this->eInfo['Equip_UpAtt'] * $level;
		$sor = $this->eInfo['Equip_Sor'] + $this->eInfo['Equip_UpSor'] * $level;
		$def = $this->eInfo['Equip_Def'] + $this->eInfo['Equip_UpDef'] * $level;
		$res = $this->eInfo['Equip_Res'] + $this->eInfo['Equip_UpRes'] * $level;

		$ehp = $this->eInfo['Equip_Hp'] + $this->eInfo['Equip_UpHp'] * $level;		
		$gethp = $this->eInfo['Equip_GetHp'] + $this->eInfo['Equip_UpGetHp'] * $level;	
		$emp = $this->eInfo['Equip_Mp'] + $this->eInfo['Equip_UpMp'] * $level;
		$getmp = $this->eInfo['Equip_GetMp'] + $this->eInfo['Equip_UpGetMp'] * $level;
		$speed = $this->eInfo['Equip_Mov'] + $this->eInfo['Equip_UpMov'] * $level;

		$AttSpd = (int)$this->eInfo['Equip_AttSpd'] + (int)$this->eInfo['Equip_UpAttSpd'] * $level;					#攻击速度
		$AttCri = (int)$this->eInfo['Equip_AttCri'] + (int)$this->eInfo['Equip_UpAttCri'] * $level;					#物理爆机
		$SorCri = (int)$this->eInfo['Equip_SorCri'] + (int)$this->eInfo['Equip_UpSorCri'] * $level;					#法术爆机
		$AttHit = (int)$this->eInfo['Equip_AttHit'] + (int)$this->eInfo['Equip_UpAttHit'] * $level;					#物理命中
		$SkiHit = (int)$this->eInfo['Equip_SkiHit'] + (int)$this->eInfo['Equip_UpSkiHit'] * $level;					#法术命中
		$pry = (int)$this->eInfo['Equip_Pry'] + (int)$this->eInfo['Equip_UpPry'] * $level;							#装备闪避
		$AttPierce = (int)$this->eInfo['Equip_AttPierce'] + (int)$this->eInfo['Equip_UpAttPierce'] * $level;		#装备护甲穿透
		$SorPierce = (int)$this->eInfo['Equip_SorPierce'] + (int)$this->eInfo['Equip_UpSorPierce'] * $level;		#装备法术穿透
		$AttSteal = (int)$this->eInfo['Equip_AttSteal'] + (int)$this->eInfo['Equip_UpAttSteal'] * $level;			#物理吸血
		$SorSteal = (int)$this->eInfo['Equip_SorSteal'] + (int)$this->eInfo['Equip_UpSorSteal'] * $level;			#法术吸血
		$CoolDown = (int)$this->eInfo['Equip_CoolDown'] + (int)$this->eInfo['Equip_UpCoolDown'] * $level;			#技能闪却

		return ( $att + $sor + $def + $res ) + floor( ( $ehp + $gethp*2 + $emp + $getmp*2 + $speed ) / 10 ) + ( abs($AttSpd) + abs($AttCri) + abs($SorCri) + abs($AttHit) + abs($SkiHit) + abs($pry) + abs($AttPierce) + abs($SorPierce) + abs($AttSteal) + abs($SorSteal) + abs($CoolDown) );
	}
 }
?>