<?php
/**
 *@ 用户背包处理
 **/
 $user = new User_User();

 $good = new User_Goods( $user->getUid() );
 $ret['list'] = $good->getAllGoods();
 
 ret( $ret );
 
?>