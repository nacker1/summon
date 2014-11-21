<?php
	class Logger extends Log{
		/**
		 *@ d 调试日志 
		 **/
		public function d($msg) {			
			if( LOG_LEVEL < 4 ){ return; }
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->debug($msg);
		}
		/**
		 *@ i 接口基本信息
		 **/
		public function i($msg) {
			if( LOG_LEVEL < 3 ){ return; }
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->info($msg);
		}
		/**
		 *@ w 警告内容
		 **/
		public function w($msg) {
			if( LOG_LEVEL < 2 ){ return; }
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->warn($msg);
		}
		/**
		 *@ e 信息错误内容
		 **/
		public function e($msg) {
			if( LOG_LEVEL < 1 ){ return; }
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->error($msg);
		}
		/**
		 *@ f 严重错误信息
		 **/
		public function f($msg) {
			if( LOG_LEVEL < 0 ){ return; }
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->fatal($msg);
		}
	}