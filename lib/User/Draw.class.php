<?php
/**
 *@ Draw抽卡类
 **/
class User_Draw extends User_Base{
	private $draw_type_table='zy_baseDrawTypeConfig';								//抽卡类型配置表
	private $draw_table='zy_baseDrawConfig';										//抽卡物品配置表
	private $type;																	//抽卡类型  1为金币  2为钻石 3为友情点
	private $dInfo;																	//指定类型抽卡配置内容
	private $userType;																//指定类型抽卡配置内容
	private $tolTypeRate=0;															//抽卡类型的各概率总和

	function __construct( $type ){
		parent::__construct();
		$this->log->i('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		$this->type = $type;
		$this->_init();
		$this->_getDrawType();			#获取可抽奖品的类型集
	}

	private function _init(){
		//初始化抽卡配置表   
		$this->pre;
		if( true || C('test') || !$this->pre->exists( 'baseDrawConfig:'.$this->type.':check' ) ){
			$this->cdb;
			#=============  初始化类型配置表  =================================================
			$ret = $this->cdb->find( $this->draw_type_table, 'id,Group_Level,Item_Type,Item_Color,Item_Random,Item_CountMin,Item_CountMax', array( 'Box_Id'=>$this->type ) );
			if( empty( $ret ) || !is_array( $ret ) ){
				$this->log->e( '类型（'.$this->type.'）对应的类型配置信息未找到。' );
				ret( 'no_type_config' ,-1);
			}
			foreach( $ret as $v ){
				$this->pre->hmset( 'baseDrawTypeConfig:'.$this->type.':'.$v['Group_Level'].':'.$v['id'], $v );
			}
			#=============  初始化物品配置表  =================================================
			$ret = $this->cdb->find( $this->draw_table, 'Group_Level,Item_Id,Item_Type,Item_Color,Item_Random', array( 'Box_Id'=>$this->type ) );
			if( empty( $ret ) || !is_array( $ret ) ){
				$this->log->e( '类型（'.$this->type.'）对应的物品配置信息未找到。' );
				ret( 'no_config' ,-1);
			}
			foreach( $ret as $v ){
				$this->pre->hmset( 'baseDrawConfig:'.$this->type.':'.$v['Item_Type'].':'.$v['Item_Color'].':'.$v['Item_Id'], $v );
			}

			$this->pre->hset( 'baseDrawConfig:'.$this->type.':check','checked', 1, get3time() );
		}
	}
/**
 *@ getGift 获取抽取的奖品信息  
 *@ param:
 *	$nums: 获取抽取奖品的数量
 **/
	function getGift( $nums ){
		$ret = array();
		if( !is_array( $this->userType ) ){
			$this->log->e( '抽奖获取类型错误，没有读取到配置信息' );
			$this->log->e( 'this->type:'.$this->type);
			ret(' no_type_config'.__LINE__,-1);
		}

		for( $i=0;$i<$nums;$i++ ){
			array_push( $ret, $this->_getGood() );
		}
		return implode('#',$ret);
	}
/**
 *@ _getGood() 返回抽中的物品信息
 **/
	private function _getGood(){
		$uLevel = $this->getLevel();
		$type = $this->_getType();
		$keys = $this->pre->keys( 'baseDrawConfig:'.$this->type.':'.$type['type'].':'.$type['color'].':*' );
		if( empty( $keys ) ){
			$this->log->e( '抽奖获取'.$this->type.'_'.$type['type'].'_'.$type['color'].'类型对应的物品出错，没有读取到配置信息' );
			ret(' no_good_config'.__LINE__,-1);
		}
		$tolRate = 0;
		$tempInfo = array();
		foreach( $keys as $v ){
			$gInfo = $this->pre->hgetall( $v );
			$Group_Level = explode(',',$gInfo['Group_Level']);
			if( $uLevel>=$Group_Level[0] ){
				$tempInfo[] = $gInfo;
				$tolRate += (int)$gInfo['Item_Random'];
			}
		}
		foreach( $tempInfo as $k=>$v ){
			$list[$k] = number_format($v['Item_Random']/$tolRate, 4);
		}
		$index = $this->retRate( $list );
		if( !isset( $tempInfo[$index] ) ){
			$this->log->e( '随机得到的概率错误，概率列表如下：'.json_encode( $list ) );
			$index = 0;
		}
		$good[]=$tempInfo[$index]['Item_Id'];
		$good[] = mt_rand( $type['min'], $type['min'] );
		if( $tempInfo[$index]['Item_Id'] < 11000 ){	#如果是英雄给定英雄的品质
			$good[] = $tempInfo[$index]['Item_Color'];
		}
		$this->setMissionId( 2,65 );
		return implode( ',', $good );
	}
/**
 *@ _getType() 返回此次抽中的奖品类型与品质
 **/
	private function _getType(){
		$ret = array();
		foreach( $this->userType as $k=>$v ){
			if( $v['Item_Random']>0 ){
				$list[$k] = number_format( $v['Item_Random']/$this->tolTypeRate, 4 );
			}
		}
		$index = $this->retRate( $list );
		$ret['type'] = $this->userType[ $index ]['Item_Type'];
		$ret['color'] = $this->userType[ $index ]['Item_Color'];
		$ret['min'] = $this->userType[ $index ]['Item_CountMin'];
		$ret['max'] = $this->userType[ $index ]['Item_CountMax'];
		return $ret;
	}
/**
 *@ _getDrawType 获取可以抽奖的类型信息
 **/
	private function _getDrawType(){
		$uLevel = $this->getLevel();
		switch (1) {
			case 0<$uLevel && $uLevel<5:#1-4级
				$flag = '1,4';
				break;
			case 4<$uLevel && $uLevel<10:#5-9级
				$flag = '5,9';
				break;
			case 9<$uLevel && $uLevel<20:#10-19级
				$flag = '10,19';
				break;
			case 19<$uLevel && $uLevel<30:#20-29级
				$flag = '20,29';
				break;
			case 29<$uLevel && $uLevel<40:#30-39级
				$flag = '30,39';
				break;
			case 39<$uLevel && $uLevel<50:#40-49级
				$flag = '40,49';
				break;
			case 49<$uLevel && $uLevel<60:#50-59级
				$flag = '50,59';
				break;
			case 59<$uLevel:#60级及以上
				$flag = '60,80';
				break;
			default:
				# code...
				$flag = '1,4';
				break;
		}

		$keys = $this->pre->keys( 'baseDrawTypeConfig:'.$this->type.':'.$flag.':*' );
		$this->log->i('typeKeys:'.json_encode( $keys ) );
		foreach( $keys as $v ){
			$info = $this->pre->hgetall( $v );
			$this->userType[] = $info;
			$this->tolTypeRate += $info['Item_Random'];
		}		
	}

}
?>