<?php
     /**
      * Simple logger class based on a similer class created by 
      *
      * @package default
      * @author huangZY <huangzy@51094.com  http://www.51094.com>
      **/
	define('LEVEL_FATAL', 0);
	define('LEVEL_ERROR', 1);
	define('LEVEL_WARN', 2);
	define('LEVEL_INFO', 3); 
	define('LEVEL_DEBUG', 4);  
    class Log{
        /**
         * log_file - the log file to write to
         *  
         * @var string
         **/
		private $tag;
        private $log_file;
		
		private $level = LEVEL_INFO;
        
        /**
         * Constructor
         * @param String logfile - [optional] Absolute file name/path. Defaults to ubuntu apache log.
         * @return void
         **/        
        function __construct($tag='public',$log_file = '/data/web/summon/logs/') {
			$this->tag = $tag;
            $this->log_file = $log_file.date('Ymd').'ddz.log';

            if(!file_exists($this->log_file)){ //Attempt to create log file    
                self::autoCreateDir($log_file);
				@touch($this->log_file);
            }
			
            //Make sure we'ge got permissions
            if(!(is_writable($this->log_file) || $this->win_is_writable($this->log_file))){   
                //Cant write to file,
                //throw new Exception("LOGGER ERROR: Can't write to log", 1);
				exit('LOGGER ERROR: Can\'t write to log');
            }
        }
		
		public function setLevel($status) {
			$this->level = $status;
		}
        
        /**
         * debug - Log Debug
         * @param String tag - Log Tag
         * @param String message - message to spit out
         * @return void
         **/      
        public function debug($message){
            $this->writeToLog("DEBUG", LEVEL_DEBUG, $message);
        }
		
        /**
         * info - Log Info
         * @param String tag - Log Tag
         * @param String message - message to spit out
         * @return void
         **/        
        public function info($message){
            $this->writeToLog("INFO ", LEVEL_INFO, $message);            
        }
		
        /**
         * warn - Log Warning
         * @param String tag - Log Tag
         * @param String message - message to spit out
         * @author 
         **/        
        public function warn($message){
            $this->writeToLog("WARN ", LEVEL_WARN, $message);            
        }
		
        /**
         * error - Log Error
         * @param String tag - Log Tag
         * @param String message - message to spit out
         * @author 
         **/        
        public function error($message){
            $this->writeToLog("ERROR", LEVEL_ERROR,  $message);            
        }
		
        /**
         * fatal - Log Fatal
         * @param String tag - Log Tag
         * @param String message - message to spit out
         * @author 
         **/        
        public function fatal($message){
            $this->writeToLog("FATAL", LEVEL_FATAL, $message);            
        }
		
        /**
         * writeToLog - writes out timestamped message to the log file as 
         * defined by the $log_file class variable.
         *
         * @param String status - "INFO"/"DEBUG"/"ERROR" e.t.c.
         * @param String tag - "Small tag to help find log entries"
         * @param String message - The message you want to output.
         * @return void
         **/        
        private function writeToLog($status, $level, $message) {
			if ($level <= $this->level) {
	            $date = date('[Y-m-d H:i:s]');
	            $msg = $date.':['.$this->tag.']['.$status.'] - '.$message.PHP_EOL;
				is_writable($this->log_file) or die('Log path not exist or can\'t write! LogPath:'.$this->log_file);
	            file_put_contents($this->log_file, $msg, FILE_APPEND);
			}
        }

        //Function lifted from wordpress
        //see: http://core.trac.wordpress.org/browser/tags/3.3/wp-admin/includes/misc.php#L537
        private function win_is_writable( $path ) {
            /* will work in despite of Windows ACLs bug
             * NOTE: use a trailing slash for folders!!!
             * see http://bugs.php.net/bug.php?id=27609
             * see http://bugs.php.net/bug.php?id=30931
             */
            if ( $path[strlen( $path ) - 1] == '/' ) // recursively return a temporary file path
                return win_is_writable( $path . uniqid( mt_rand() ) . '.tmp');
            else if ( is_dir( $path ) )
                return win_is_writable( $path . '/' . uniqid( mt_rand() ) . '.tmp' );
            
            // check tmp file for read/write capabilities
            $should_delete_tmp_file = !file_exists( $path );
            $f = @fopen( $path, 'a' );
            if ( $f === false )
                return false;
            
            fclose( $f );

            if ( $should_delete_tmp_file )
                unlink( $path );

            return true;
        } 
		
		/**
		 *@ autoCreateDir 自动创建目录
		 **/
		 function autoCreateDir($path){
			if( is_dir($path) ) return true;
			$dirname = dirname($path);
			if ( !is_dir($dirname) ){
				self::autoCreateDir($dirname);
			}
			!is_writeable($dirname) && die('Error：'.$dirname.' can\' be write !!');
			@mkdir($path,0777) or die('mkdir fail:'.$path);
			return chmod($path,0777);
		}
    }
?>