<?php
/**
 *@ Draw抽卡类
 **/
class User_Draw extends User_Base{
	private $draw_type_table='zy_baseDrawTypeConfig';								//抽卡类型配置表
	private $draw_table='zy_baseDrawConfig';										//抽卡物品配置表
	private $type;																	//抽卡类型  1为金币  2为钻石 3为友情点  10赏金之路普通抽
	private $dInfo;																	//指定类型抽卡配置内容
	private $userType;																//指定类型抽卡配置内容
	private $tolTypeRate=0;															//抽卡类型的各概率总和
	private $giveHeroTag = true;													//10连抽必送英雄
	private $groupLevel;															//对应宝箱类型表中的Group_Level字段   有可能是等级，有可能是赏金之路通关层数

	function __construct( $type, $groupLevel='' ){
		parent::__construct();
		$this->log->d('~~~~~~~~~~~~~~~~~~  '.__CLASS__.' ~~~~~~~~~~~~~~~~~~');
		if( empty( $groupLevel ) ){
			$this->groupLevel = $this->getLevel();
		}else{
			$this->groupLevel = $groupLevel;
		}
		$this->type = $type;
		$this->_init();
		$this->_getDrawType();			#获取可抽奖品的类型集
	}

	private function _init(){
		//初始化抽卡配置表   
		$this->pre;
		if( C('test') || !$this->pre->exists( 'baseDrawConfig:'.$this->type.':check' ) ){
			$this->cdb;
			$this->log->d('+++++++++++++++++ DB select ++++++++++++++++');
			$this->pre->hdel('baseDrawTypeConfig:*');
			$this->pre->hdel('baseDrawConfig:*');
			#=============  初始化类型配置表  =================================================
			$ret = $this->cdb->find( $this->draw_type_table, 'id,Group_Level,Item_Type,Item_Color,Item_Random,Item_CountMin,Item_CountMax', array( 'Box_Id'=>$this->type ) );
			if( empty( $ret ) || !is_array( $ret ) ){
				$this->log->e( '类型（'.$this->type.'）对应的类型配置信息未找到。' );
				ret( 'db_no_type_config' ,-1);
			}
			foreach( $ret as $v ){
				#$this->pre->hmset( 'baseDrawTypeConfig:'.$this->type.':'.$v['Group_Level'].':'.$v['id'], $v );
				$rret[$v['Group_Level']][ $v['id'] ] = $v;
			}
			#$this->log->i('rret:'.json_encode($rret));
			foreach ($rret as $key => $value) {
				# code...
				$this->pre->set( 'baseDrawTypeConfig:'.$this->type.':'.$key, json_encode($value) );
			}
			#=============  初始化物品配置表  =================================================
			unset($ret);
			$ret = $this->cdb->find( $this->draw_table, 'Group_Level,Item_Id,Item_Type,Item_Color,Item_Random,Customs_Grade', array( 'Box_Id'=>$this->type ) );
			if( empty( $ret ) || !is_array( $ret ) ){
				$this->log->e( '类型（'.$this->type.'）对应的物品配置信息未找到。' );
				ret( 'db_no_goods_config' ,-1);
			}
			
			foreach( $ret as $v ){
				#$this->pre->hmset( 'baseDrawConfig:'.$this->type.':'.$v['Item_Type'].':'.$v['Item_Color'].':'.$v['Item_Id'], $v );
				$temp = $this->type.':'.$v['Item_Type'].':'.$v['Item_Color'];
				$ret[ $temp ][ $v['Item_Id'] ] = $v;
			}

			foreach( $ret as $key=>$val ){
				$this->pre->set( 'baseDrawConfig:'.$key, json_encode( $val ) );
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

		if( $nums == 10 && $this->getLevel() > 1 ){
			for( $i=0;$i<$nums-1;$i++ ){
				$type = $this->_getType();
				array_push( $ret, $this->_getGood( $type ) );
			}
			array_push( $ret, $this->_giveHero() );
		}else{
			if( $this->getLevel() < 2 ){
				if( $this->type != 2 )
					$this->giveHeroTag = false;
				array_push( $ret, $this->_giveHero() );
			}else{
				$type = $this->_getType();
				array_push( $ret, $this->_getGood( $type ) );
			}
		}

		$this->setMissionId( 2,65,$nums );
		$this->log->d( 'goods:'.json_encode($ret) );
		return $ret;
	}

	private function _giveHero(){
		$type = $this->_getType();
		if( $this->giveHeroTag ){
			if( $this->type == 2 ){  #送英雄
				$type['type'] = 1;
				$type['color'] = 1;
				$type['min'] = 1;
				$type['max'] = 1;
			}elseif( $this->type == 1 ){  #送蓝色物品
				$item = array( 3=>0.05, 4=>0.95 );
				$itemType = $this->retRate( $item );
				if( !isset( $item[ $itemType ] ) ) $itemType = 4;
				$type['type'] = $itemType;
				$type['color'] = 3;
				$type['min'] = 1;
				$type['max'] = 1;
			}
			$this->log->d( '10_draw 连抽送英雄：'.json_encode($type) );
		}
		return $this->_getGood( $type );
	}
/**
 *@ _getGood() 返回抽中的物品信息
 **/
	private function _getGood( $type ){
		$uLevel = $this->getLevel();
		if( $this->type == 2 ){
			if( $type['type'] == 1 && $type['color'] != 0 ){ $this->giveHeroTag = false; }  #抽中英雄
		}elseif(  $this->type == 1  ){
			if( $type['color'] == 3 ){ $this->giveHeroTag = false; }  #抽中蓝色物品
		}
#================================== 取物品 ==================================
		$goods = json_decode( $this->pre->get( 'baseDrawConfig:'.$this->type.':'.$type['type'].':'.$type['color'] ), true );
		$this->log->d( 'baseItemConfig:'.json_encode($goods) );
		if( empty( $goods ) ){
			$this->log->e( '抽奖获取'.$this->type.'_'.$type['type'].'_'.$type['color'].'类型对应的物品出错，没有读取到配置信息' );
			ret(' no_good_config'.__LINE__,-1);
		}
		$tolRate = 0;
		$tempInfo = array();
		foreach( $goods as $v ){
			$Group_Level = explode(',',$v['Group_Level']);
			if( $uLevel>=$Group_Level[0] && $uLevel<=$Group_Level[1] ){
				if( empty( $v['Customs_Grade'] ) ){
					$tempInfo[] = $v;
					$tolRate += (int)$v['Item_Random'];
				}else{
					$customs = explode( ',', $v['Customs_Grade'] );
					if( $this->groupLevel >= $customs[0] && $this->groupLevel <= $customs[1] ){
						$tempInfo[] = $v;
						$tolRate += (int)$v['Item_Random'];
					}
				}
			}
		}
		$this->log->d( 'tempInfo:'.json_encode($tempInfo) );
#================================== END ==================================

		foreach( $tempInfo as $k=>$v ){
			$list[$k] = number_format($v['Item_Random']/$tolRate, 4);
		}
		$index = $this->retRate( $list );
		if( !isset( $tempInfo[$index] ) ){
			$this->log->e( '随机得到的概率错误，概率列表如下：'.json_encode( $list ) );
			$index = 0;
		}
		$good[]= isset( $tempInfo[$index]['Item_Id'] ) ? $tempInfo[$index]['Item_Id'] : 64002;					#默认物品 64002
		$good[] = isset( $type['min'] ) && isset( $type['max'] ) ? mt_rand( $type['min'], $type['max'] ) ? 1;	#默认个数 1 个
		if( $tempInfo[$index]['Item_Id'] < 11000 ){	#如果是英雄给定英雄的品质
			$good[] = isset( $tempInfo[$index]['Item_Color'] ) ? $tempInfo[$index]['Item_Color'] : 1;
		}
		$this->log->d( 'getGoods:'.json_encode($good) );
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
		$ret['type'] = isset( $this->userType[ $index ]['Item_Type'] ) ? (int)$this->userType[ $index ]['Item_Type'] : 6;
		$ret['color'] = isset( $this->userType[ $index ]['Item_Color'] ) ? (int)$this->userType[ $index ]['Item_Color'] : 0;
		$ret['min'] = isset( $this->userType[ $index ]['Item_CountMin'] ) ? (int)$this->userType[ $index ]['Item_CountMin'] : 1;
		$ret['max'] = isset( $this->userType[ $index ]['Item_CountMax'] ) ? (int)$this->userType[ $index ]['Item_CountMax'] : 1;
		$this->log->d('drawType:'.json_encode($ret));
		return $ret;
	}
/**
 *@ _getDrawType 获取可以抽奖的类型信息
 **/
	private function _getDrawType(){
		$uLevel = $this->groupLevel;
		switch (1) {
			case 0<$uLevel && $uLevel<10:#1-9级
				$flag = '1,9';
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
			case 59<$uLevel && $uLevel<70:#60-69级
				$flag = '60,69';
				break;
			case 69<$uLevel && $uLevel<80:#70-79级
				$flag = '70,79';
				break;
			case 79<$uLevel && $uLevel<90:#80-89级
				$flag = '80,89';
				break;
			case 89<$uLevel && $uLevel<=100:#90-100级
				$flag = '90,100';
				break;
			default:
				# code...
				$flag = '80,80';
				break;
		}
		$this->log->d('Group_Level:'.$flag);
		$ret = $this->pre->get( 'baseDrawTypeConfig:'.$this->type.':'.$flag );
		$this->log->d( 'groupLevelTypeConfig:'.$ret );
		$ret = json_decode($ret,true);
		foreach( $ret as $v ){
			$this->log->d( 'draw_goodType_info:'.json_encode($v) );
			$this->userType[] = $v;
			$this->tolTypeRate += $v['Item_Random'];
		}		
	}

}
?>