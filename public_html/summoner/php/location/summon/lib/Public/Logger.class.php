<?php
	class Logger extends Log{
		public function d($msg) {
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			$this->debug($msg);
		}
		public function i($msg) {
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->info($msg);
		}
		public function w($msg) {
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			$this->warn($msg);
		}
		public function e($msg) {
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			$this->error($msg);
		}
		public function f($msg) {
			if( is_array( $msg ) ){
				$msg = json_encode($msg);
			}
			ISLOG && $this->fatal($msg);
		}
	}