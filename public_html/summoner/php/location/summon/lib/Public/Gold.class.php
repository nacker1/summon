<?php
/**
 *@ 黄金矿山配置
 **/
class Gold extends Base{
	private $gold_table='zy_baseActivity';			#黄金矿山配置表
	private $time;									#当日领取次数
	private $config;								#本次修炼的配置信息

	function __construct( $time ){
		parent::__construct();
		$this->log->d( '~~~~~~~~~~~~~~~~~~~~  Gold  ~~~~~~~~~~~~~~~~~~~~~~~' );
		$this->time = $time;
		$this->_init();
	}

	private function _init(){
		$this->pre;
		if( true || C('test') || !$this->pre->exists( $this->gold_table.':check' ) ){
			$this->cdb;
			$ret = $this->cdb->find( $this->gold_table );
			if( empty( $ret ) ){
				$this->log->e( '@@@@ SELECT_DB_NULL @@@@' );	
			}
			$this->log->d( '~~~~~~~~~~~~~~~~~~~~  SELECT_DB  ~~~~~~~~~~~~~~~~~~~~~~~' );
			foreach ($ret as $v ) {
				$set['time'] = $v['Mine_Countdown'];
				$set['cooldouMin'] = $v['Diamond_CountMin'];
				$set['cooldouMax'] = $v['Diamond_CountMax'];
				$set['moneyMin'] = $v['Gold_CountMin'];
				$set['moneyMax'] = $v['Gold_CountMax'];
				$this->pre->set( $this->gold_table.':'.$v['Mine_Times'], json_encode( $set ) );
			}

			$this->pre->set( $this->gold_table.':check', 1, get3time() );
		}
		$this->config = json_decode( $this->pre->get( $this->gold_table.':'.$this->time ), true);
		$this->log->i( 'gold_config:'.json_encode($this->config) ); 
	}

	function getConfig(){
		$this->log->i( $this->config );
		return $this->config;
	}

	function getTime(){
		$this->log->i( 'gold_config:'.json_encode($this->config) ); 
		return $this->config['time'];
	}
	function getReward(){
		$this->log->i( $this->config );
		$config = $this->getConfig();
		$ret['money'] = mt_rand( $config['moneyMin'], $config['moneyMax'] );
		$ret['cooldou'] = mt_rand( $config['cooldouMin'], $config['cooldouMax'] );
		return $ret;
	}
	function getNextTime(){
		$nextConfig = $this->pre->get( $this->gold_table.':'.( $this->time+1 ) );
		if( empty( $nextConfig ) ){
			return 0;
		}
		$this->log->i( 'gold_next_config:'.$nextConfig);
		$nextConfig = json_decode( $nextConfig,true );
		return $nextConfig['time'];
	}
}
?>