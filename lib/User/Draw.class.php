<?php
/**
 *@ Draw抽卡类
 **/
class User_Draw extends User_Base{
	private $draw_table='zy_baseDrawConfig';					//抽卡配置表
	private $type;									//抽卡类型  1为金币  2为钻石 3为友情点
	private $dInfo;									//指定类型抽卡配置内容

	function __construct( $type ){
		parent::__construct();
		$this->type = $type;
		$this->_init();
	}

	private function _init(){
		//初始化抽卡配置表   
		$this->pre;
		if( C('test') || !$this->pre->hget( 'baseDrawConfig:check','checked' ) ){
			$this->cdb;
			$ret = $this->cdb->find( $this->draw_table );
			if( empty( $ret ) || !is_array( $ret ) ){
				$this->log->e( '类型（'.$this->type.'）对应的配置信息未找到。' );
				ret( 'no_config' ,-1);
			}
			foreach( $ret as $v ){
				$this->pre->hmset( 'baseDrawConfig:'.$this->type.':'.$v['Item_Id'], $v );
			}
			$this->pre->hset( 'baseDrawConfig:check','checked', 1, get3time() );
		}
		$keys = $this->pre->keys( 'baseDrawConfig:'.$this->type.':*' );

		foreach( $keys as $v ){
			$info = $this->pre->hgetall( $v );
			$levelLimit = explode(',',$info['Group_Level']);
			if( $this->getLevel() >= $levelLimit[0] && $this->getLevel() < $levelLimit[1] ){
				$this->dInfo[] = $info;
			}
		}
	}
/**
 *@ getGift 获取抽取的奖品信息  
 *@ param:
 *	$nums: 获取抽取奖品的数量
 **/
	function getGift( $nums ){
		$ret = array();
		if( !is_array( $this->dInfo ) ){
			ret(' no_config '.__LINE__,-1);
		}
		foreach( $this->dInfo as $k=>$v ){
			if( $v['Item_Rate']>0 ){
				$list[$k] = $v['Item_Rate']/10000;
			}else{
				$oList[] = $v;
			}
		}
		for( $i=0;$i<$nums;$i++ ){
			$index = $this->retRate( $list );
			if( isset($this->dInfo[$index]) ){
				$temp = $this->dInfo[$index];
			}else{
				$index = mt_rand(0, ( count( $oList )-1 ) );
				$temp = $oList[$index];
			}
			$good[]= $temp['Item_Id'];
			$good[] = mt_rand($temp['Item_CountMin'],$temp['Item_CountMax']);
			if( $temp['Item_Id'] < 11000 ){	#如果是英雄给定英雄的品质
				$good[] = $temp['Hero_Color'];
			}
			array_push( $ret, implode(',',$good) );
			unset($good);
		}
		return implode('#',$ret);
	}
}
?>