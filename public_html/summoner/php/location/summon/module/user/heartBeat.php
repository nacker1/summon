<?php
/**
 *@ php 心跳接口
 **/
 $user = new User_User();

 $lastHeartTime = isset($input['lht'])?$input['lht']:0;
 ret( $user->getHeartBeatInfo() );

?>