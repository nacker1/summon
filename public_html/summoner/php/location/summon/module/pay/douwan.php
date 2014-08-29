<?php
/**
 *@ 豆玩充值回调
 **/
	$user = new User_User();
	//$user->setMonthCode();
	dump($_SERVER);
	ret( $user->getUserInfo() );
?>