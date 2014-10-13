<?php
/**
 *@ Pay 公共充值类   发货有公共充值类统一处理
 **/
class Pay extends Base{
	private $payLogTable='zy_statsPayLog';						//充值流水表
	private $money;												//充值金额
	private $channel;											//充值渠道
	private $orderid;											//充值订单id  来自充值方
	private $status=1;											//充值状态 1成功 0为失败
				
	private $errorInfo;											//错误信息

	function __construct( $payinfo ){
		parent::__construct($payinfo['uid']);
		$this->money 		= 	$payinfo['money'];
		$this->channel 		= 	$payinfo['channel'];
		$this->isMonth 		= 	$payinfo['isMonth'];
		$this->orderid 		= 	$payinfo['orderid'];
		$this->payType		= 	$payinfo['tag'];
		$this->serverId 	= 	$payinfo['sid'];
		$this->cond = new Cond( 'uniqPay_'.$this->serverId, $payinfo['uid'], 600 );
		$this->_init();
	}
/**
 *@ 初始化充值信息  判断订单重复性
 **/
	private function _init(){
		if( $this->cond->get( $this->orderid ) ){
			$this->status = 0;
			$this->errorInfo = '订单重复';
		}
	}
/**
 *@ 开始充值
 **/
	function pay(){
		if( !$this->getStatus() ){
			return;
		}
		$server = new Server( $this->serverId );
		$url = $server->getServerPhpUrl();
		$param['cmd'] = 9040;
		$param['k'] = '56d16d95fe54b6e69aec0fc8f1c71cf1';
		$param['ver'] = '1.0.0';
		$param['sid'] = $this->serverId;
		$param['uid'] = $this->uid;
		$param['skey'] = -1;
		$url .= '/api.php?'.http_build_query($param);
		unset( $param );
		$this->log->i('url:'.$url);
		$curl = new Curl( $url );
		$sendPay[] = $this->uid;
		$sendPay[] = $this->money;
		$sendPay[] = $this->payType;
		$sendPay[] = $this->isMonth;

		$param['pay'] = implode( '|', $sendPay );
		$ret = msgpack_unpack( $curl->post( msgpack_pack( $param ) ) );
		if( $ret['Ret'] != 0 ){
			$this->status = 0;
			$this->errorInfo = $ret['desc'];
		}
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
	public function getStatus(){
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
		$pay['payType'] = $this->payType;
		$pay['sid'] = $this->serverId;
		$pay['uid'] = $this->uid;
		$pay['orderid'] = $this->orderid;
		$pay['time'] = date('Y-m-d H:i:s');
		$this->throwSQL( $this->payLogTable, $pay, '', 1, 'stats' );
	}
}
?>