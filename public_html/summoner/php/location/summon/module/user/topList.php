<?php
/**
 *@ 排行榜接口
 **/
	$user = new User_User();

	$type = isset( $input['t'] ) ? $input['t'] : 0;

	$ref = isset( $input['ref'] ) ? $input['ref'] : 0;

	if( empty($type) ){ret(' YMD ',-1);}

	$top = new Top( $type );
	
	if( !empty($ref) ){
		$top->setTopList();
	}
	$ret['topList'] = $top->getTopList();
	
	ret( $ret );
?>