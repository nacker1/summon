<?php
/**
 *@ 英雄基类
 **/
class Herobase extends Base{
	protected $table = 'zy_baseHero'; //英雄基类表
	protected $hid;		//英雄id

	public function __construct( $hid='' ){
		$this->hid = $hid;
		parent::__construct();
	}
}
?>