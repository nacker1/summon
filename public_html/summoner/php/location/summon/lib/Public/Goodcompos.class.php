<?php
/**
 *@ 物品合成、强化类
 **/
class Goodcompos extends Goodbase{
	private $cominfo;	//道具合成信息
	private $opt;		//操作类型  1为合成，2为强化

	public function __construct( $gid, $type=1 ){
		parent::__construct($gid);
		$this->opt = $type;
		$this->_init();
	}

	private function _init(){
		if( $this->opt == 1 ){
			if( !$this->pre->exists('goodBase:compos:'.$this->gid) ){
				ret('道具（'.$this->gid.'）不存在',-1);
			}
			$this->cominfo = $this->pre->hgetall( 'goodBase:compos:'.$this->gid );
			$this->log->d( 'composConfig:'.json_encode($this->cominfo) );
		}else{
			if( !$this->checkLevel() ){
	 			ret( 'max_level_'.__LINE__, -1 );
	 		}
		}
	}
	
	public function getComItemInfo(){
		return $this->cominfo;
	}
/**
 *@ 获取装备合成需要的金币数量
 **/
	public function getComMoney(){
		return $this->cominfo['Cost_Gold'];
	}
/**
 *@ 获取装备强化需要的能量点
 **/
	public function getEnergy(){
		//$eList 装备升级需要的能量点配置信息， $eList的下标为装备品质等级，最后的数组为等级对应的能量点数
		$eList = array(
				1=>array(),							#白色品质
				2=>array(10),						#绿色品质
				3=>array(30,50,80),					#蓝色品质
				4=>array(60,100,160,300,500),		#紫色品质
				5=>array(60,100,160,300,500),		#橙色品质
			);
		$gLevel = $this->getColor();
		return (int)$eList[$gLevel][ $this->getEquipLevel() ];
	}
}

?>