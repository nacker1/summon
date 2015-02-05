<?php
/**
 *@ 物品基础类
 **/
 class Goodbase extends Pbase{
	private $base_table='zy_baseItem';	//基础物品表
	private $equip_table='zy_baseEquip';	//装备配置表
	private $compos_table='zy_baseGoodCompound';	//基础物品表
	protected $gid;		//物品id
	protected $equid;	//物品对应的装备id
	protected $type;	//物品类型  1	英雄碎片 2	英雄符文 3	英雄装备 4	合成卷轴 5	装备碎片 6	恢复召唤师体力道具 7	增加英雄经验道具 8	提高装备强化率道具 9	提高英雄属性道具
	protected $ginfo;	//物品信息

	
	public function __construct( $gid='' ){
		parent::__construct();
		$this->type = substr($gid,0,1);
		$this->gid = $gid;
		$this->equid = substr($this->gid,0, 5);
		$this->_init();
	}

	private function _init(){
		if( C('test') || !$this->pre->hget('goodBase:base_check','check') ){ //查库
			$this->cdb;$this->preMaster;
			$this->preMaster;
			$this->preMaster->hdel('goodBase:base:*');
			$ret = $this->cdb->find($this->base_table);
			if( $ret && is_array($ret) ){
				foreach( $ret as $v ){
					$this->preMaster->hmset('goodBase:base:'.$v['Item_Id'], $v);
				}
			}
			$this->preMaster->hset('goodBase:base_check','check',1, get3time());
		}

		if( !empty( $this->gid ) ){
			$this->ginfo = $this->pre->hgetall('goodBase:base:'.$this->gid);
			if( empty( $this->ginfo ) ){
				ret('config_no_item_'.$this->gid,-1);
			}
		}else{
			$keys = $this->pre->keys('goodBase:base:*');
			foreach( $keys as $v ){
				$this->ginfo[] = $this->pre->hgetall($v);
			}
		}

		if( C('test') || !$this->pre->hget('goodBase:equip_check','check') ){
			$this->cdb;$this->preMaster;
			$this->preMaster;
			$this->preMaster->hdel('goodBase:equip:*');
			$ret = $this->cdb->find($this->equip_table);
			if( $ret && is_array($ret) ){
				foreach( $ret as $v ){
					$this->preMaster->hmset('goodBase:equip:'.$v['Equip_Id'], $v);
				}
			}
			$this->preMaster->hset('goodBase:equip_check','check',1, get3time());
		}

		if( C('test') || !$this->pre->hget('goodBase:compos_check','check') ){
			$this->cdb;$this->preMaster;
			$this->preMaster->hdel('goodBase:compos:*');
			$ret = $this->cdb->find($this->compos_table);
			if( $ret && is_array($ret) ){
				foreach( $ret as $v ){
					$this->preMaster->hmset('goodBase:compos:'.$v['Item_Id'], $v);
				}
			}
			$this->preMaster->hset('goodBase:compos_check','check',1, get3time());
		}
		if( 3 == $this->type ){
			$this->ginfo['color']  = $this->pre->hget( 'goodBase:equip:'.$this->equid,'Equip_Color' );		//装备品质等级
			$this->ginfo['mLevel'] = $this->pre->hget( 'goodBase:equip:'.$this->equid,'Equip_upgrade' );		//可强化次数
		}
	}

	public function getMaxLevel(){
		return (int)$this->ginfo['mLevel'];
	}

	public function getEquipLevel(){
		return (int)substr( $this->gid, 5 );
	}

	public function getGid(){
		return $this->gid;
	}

	public function getColor(){
		return empty($this->ginfo['color']) ? 0 : (int)$this->ginfo['color'];
	}

	public function getType(){
		return $this->type;
	}

	public function getGoodName(){
		return $this->ginfo['Item_Name'];
	}
/*
 * @ 指定物品是否支持叠放
 */
	public function getGoodSuper(){
		return $this->ginfo['Item_Add'];
	}
/*
 * @ 获取指定物品的消售价格
 */
	public function getSellPrice(){
		return $this->ginfo['Item_Price'];
	}
/*
 * @ 指定物品的配置信息
 */
	public function getGoodConfig(){
		$config = json_decode( $this->ginfo['Item_value'], true );
		#$this->log->i( $this->ginfo['Item_value'] );
		if( is_array( $config ) ){
			$config['gid'] = $this->gid;
		}
		return $config;
	}

	public function getGoodsSuper(){
		$ret = array();
		foreach( $this->ginfo as $v ){
			$ret[ $v['Item_Id'] ] = $v['Item_Add'];
		}
		return $ret;
	}
/**
 *@getAllBaseGood 返回所有基础物品信息
**/
	public function getAllBaseGood(){
		return $this->ginfo;
	}
/**
 *@checkLevel 检查当前物品是否可以继续强化
 **/
	public function checkLevel(){
		$level = (int)substr($this->gid,5);
		if( $level >= $this->getMaxLevel() ){
			return false;
		}
		return true;
	}
 }
?>