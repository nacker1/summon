<?php
/**
 *@ Vip 配置类 
 **/
class Vip extends Pbase{
	private $vipTable='zy_baseVipConfig';	//vip配置表
	private $maxVipLevel = 15;		//最大vip等级
	private $vLevel;				//vip等级
	private $vipConfig;			//当前指定vip等级的配置信息

	public function __construct( $vLevel = 0 ){
		parent::__construct();
		$this->vLevel = empty($vLevel) ? 0 : (int)$vLevel ;
		$this->_init();
	}
	private function _init(){
		if( C('test') || !$this->pre->hget( 'vipConfig:check','check' ) ){
			$this->cdb;
			$ret = $this->cdb->find( $this->vipTable );
			foreach( $ret as $v ){
				$this->pre->hmset( 'vipConfig:'.$v['Vip_Level'],$v );
			}
			$this->pre->hset( 'vipConfig:check', 'check' , 1, 86400 );
		}
		$this->vipConfig = $this->pre->hgetall( 'vipConfig:'.$this->vLevel );
	}
/**
 *@ 获取当前vip等级对应的 好友额外名额
 **/
	public function getExtFriends(){
		return $this->vipConfig[ 'Vip_Friend' ];
	}
/**
 *@ 获取当前vip等级对应的 免费扫荡次数
 **/
	public function getExtSweep(){
		return $this->vipConfig[ 'Vip_Sweep' ];
	}
/**
 *@ 获取当前vip等级对应的 体力购买次数
 **/
	public function getExtBuyAction(){
		return $this->vipConfig[ 'Vip_BuyAction' ];
	}
/**
 *@ 获取当前vip等级对应的 兑换金币次数
 **/
	public function getExtExchange(){
		return $this->vipConfig[ 'Vip_Exchange' ];
	}
/**
 *@ 获取当前vip等级对应的 竞技场购买次数
 **/
	public function getExtArena(){
		return $this->vipConfig[ 'Vip_Arena' ];
	}
/**
 *@ 获取当前vip等级对应的 重置精英关卡次数
 **/
	public function getExtGeneralPve(){
		return $this->vipConfig[ 'Vip_GeneralPve' ];
	}
/**
 *@ 获取当前vip等级对应的 重置炼狱关卡次数
 **/
	public function getExtDifficultyPve(){
		return $this->vipConfig[ 'Vip_DifficultyPve' ];
	}
/**
 *@ 获取当前召唤师指定下标的次数
 **/
	public function getTagValue( $tag ){
		return $this->vipConfig[ $tag ];
	}
/**
 *@ getVipLevelByExp() 根据vip经验得到该经验对应的vip等级
 **/
	public function getVipLevelByExp( $exp ){
		$retVipLevel = $this->vLevel;
		$keys = $this->pre->keys( 'vipConfig:*' );
		foreach( $keys as $v ){
			$vipInfo = $this->pre->hmget( $v,array( 'Vip_Level','Vip_Exp' ) );
			if( !empty( $vipInfo )  && isset( $vipInfo['Vip_Exp'] )  && $vipInfo['Vip_Level'] > $this->vLevel ){
				if( $vipInfo[ 'Vip_Exp' ] <= $exp &&  $vipInfo[ 'Vip_Level' ] > $retVipLevel ){
					$retVipLevel = $vipInfo[ 'Vip_Level' ];
				}
			}
		}
		return $retVipLevel;
	}
}
?>