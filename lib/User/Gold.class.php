<?php
/**
 *@ 黄金矿山配置
 **/
class User_Gold extends User_Base{
	private $gold_table='zy_baseActivity';			#黄金矿山配置表
	private $time;									#当日领取次数
	private $config;								#本次修炼的配置信息

	function __construct( $uid, $time ){
		parent::__construct( $uid );
		$this->time = $time;
		$this->_init();
	}

	private function _init(){
		$this->pre;
		if( C('test') || !$this->pre->exists( $this->gold_table.':check' ) ){
			$this->cdb;
			$ret = $this->cdb->find( $this->gold_table );
			foreach ($ret as $v ) {
				# code...
				$temp['time'] = $v['Mine_Countdown'];
				if( !empty( $v['Mine_ItemReward1'] ) ){
					$goods[] = str_replace( '#',',',$v['Mine_ItemReward1'] );
				}
				if( !empty( $v['Mine_ItemReward2'] ) ){
					$goods[] = str_replace( '#',',',$v['Mine_ItemReward2'] );
				}
				if( !empty( $v['Mine_ItemReward3'] ) ){
					$goods[] = str_replace( '#',',',$v['Mine_ItemReward3'] );
				}
				$good['good'] = implode('#',$goods);
				if( !empty( $v['Mine_Diamond'] ) ){
					$good['cooldou'] = $v['Mine_Diamond'];
				}
				if( !empty( $v['Mine_Gold'] ) ){
					$good['money'] = $v['Mine_Gold'];
				}
				$temp['reward'] = $good;
				$this->pre->hset( $this->gold_table.':'.$v['Mine_Time'], $temp );
				unset($goods);
				unset($temp);
				unset($good);
			}

			$this->pre->set( $this->gold_table.':check', 1, get3time() );
		}
		$this->config = $this->pre->hgetall( $this->gold_table.':'.$this->time );
	}

	function getConfig(){
		return $this->config;
	}

	function getTime(){
		return $this->config['time'];
	}
	function getReward(){
		return $this->config['reward'];
	}
	function getNextTime(){
		$nextConfig = $this->pre->hgetall( $this->gold_table.':'.($this->time+1) );
		if( empty( $nextConfig ) ){
			return 0;
		}
		return $nextConfig['time'];
	}
}
?>