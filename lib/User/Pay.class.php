<?php
/**
 *@ 用户充值统一处理类
 **/
class User_Pay extends User_Base{
	private $payLogTable='zy_statsPayLog';						//充值流水表
	private $money;						//充值金额
	private $channel;						//充值渠道
	private $orderid;						//充值订单id  来自充值方
	private $status;							//充值状态 0成功 1为失败

	private $errorInfo;						//错误信息

	function __construct( $payinfo ){
		if( empty( $payinfo['uid'] ) ){
			$this->status = 1;
			$this->errorInfo = 'uid_error '.__LINE__;
		}
		parent::__construct( $payinfo['uid'] );
		$this->money = $payinfo['money'];
		$this->channel = $payinfo['channel'];
		$this->orderid = $payinfo['orderid'];
		$this->cond = new Cond( 'uniqPay', $payinfo['uid'], 600 );
		$this->_init();
	}
/**
 *@ 初始化充值信息  判断订单重复性
 **/
	private function _init(){
		if( $this->cond->get( $this->orderid ) ){
			$this->status = 1;
			$this->errorInfo = 'order_done '.__LINE__;
		}
	}
/**
 *@ 开始充值
 **/
	function pay(){
		if( $this->getStatus() ){
			return false;
		}
		$this->addCooldou($this->money);  //添加钻石
		$this->addTotalPay($this->money);  //添加用户充值总金额

		$this->cond->set( $this->orderid );  //添加订单临时记录
		return true;
	}
/**
 *@ 获取充值错误信息
 **/
	function getError(){
		return $this->errorInfo;
	}
/**
 *@ 获取充值状态
 **/
	private function getStatus(){
		return $this->status;
	}
/**
 *@ __destruct() 
 *	记录充值订单
 **/
	function __destruct(){
		$pay['money'] = $this->money;
		$pay['channel'] = $this->channel;
		$pay['status'] = $this->getStatus();
		#$pay['error'] = $this->getError();
		$pay['sid'] = $this->getServerId();
		$pay['uid'] = $this->getUid();
		$pay['orderid'] = $this->orderid;
		$pay['time'] = date('Y-m-d H:i:s');
		$this->setThrowSQL( $this->payLogTable, $pay, '', 1, 'stats' );
	}
}

?>