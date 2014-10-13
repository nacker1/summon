<?php
/**
 *@ 豆玩充值回调   ext格式： sidauidaisMonth  大区id + 用户uid + 是否充值月卡    使用字母 a 连接
 **/
	$money = $input['price'];
	$ext = $input['ext'];

	$info = explode('a',$ext);

	$payinfo['sid'] = $info[0];
	$payinfo['uid'] = $info[1];
	$payinfo['money'] = $money;
	$payinfo['channel'] = 'douwan';
	$payinfo['isMonth'] = $info[2];
	$payinfo['orderid'] = $input['orderid'];
	$payinfo['tag'] = $tag;
	$pay = new Pay( $payinfo );
	$pay->pay();
	if( !$pay->getStatus() ){
		ret( $pay->getError() );
	}

	ret( 'suc' );
?>