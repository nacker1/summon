<?php
/**
 *@ 豆玩充值回调
 **/
	$money = $input['price'];
	$ext = $input['ext'];
	
	$info = explode('a',$ext);
	ret( $user->getUserInfo() );
?>