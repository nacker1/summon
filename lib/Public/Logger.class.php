<?php
	class Logger extends Log{
		public function d($msg) {
			$this->debug($msg);
		}
		public function i($msg) {
			ISLOG && $this->info($msg);
		}
		public function w($msg) {
			$this->warn($msg);
		}
		public function e($msg) {
			$this->error($msg);
		}
		public function f($msg) {
			ISLOG && $this->fatal($msg);
		}
	}