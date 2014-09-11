<?php
/**
 *@ 英雄基类
 **/
class Herobase extends Base{
	protected $heroBaseTable = 'zy_baseHero'; 					//英雄基类表
	protected $hid;												//英雄id
	static protected $hInfo;									//指定英雄信息

	public function __construct( $hid='' ){
		$this->hid = $hid;
		parent::__construct();
		$this->_init();
	}

	private function _init(){
		$this->pre;
		if( true || C('test') || !$this->pre->exists('heroBase:check') ){
			$this->cdb;
			$ret = $this->cdb->find( $this->heroBaseTable,'Hero_Hp,Hero_UpHp,Hero_Mp,Hero_UpMp,Hero_Att,Hero_UpAtt,Hero_Sor,Hero_UpSor,Hero_Def,Hero_UpDef,Hero_Res,Hero_UpRes,Hero_GetHp,Hero_UpGetHp,Hero_GetMp,Hero_UpGetMp,Hero_AttSpd,Hero_UpAttSpd,Hero_Mov,Hero_Pry',array( 'Hero_Id'=>array('<'=>20000) ) );
			#dump($this->cdb->getLastSql());
			$this->log->i($this->cdb->getLastSql());
			if( empty($ret) ){
				ret('no_baseHero_config');
			}
			foreach( $ret as $v ){
				$this->pre->hmset( 'heroBase:heroinfo:'.$v['Hero_Id'], $v );
			}
			$this->pre->hset( 'heroBase:check', 'check', 1, get3time() );
		}
		if( empty( self::$hInfo ) )
			self::$hInfo = $this->pre->hgetall( 'heroBase:heroinfo:'.$this->hid );
	}

	public function get(){

	}
}
?>