<?php

define('START_TIME',microtime(1)); 
//默认输出全部错误（可在配置文件中定义错误显示级别：1为显示执行错误，2为显示所有错误）
error_reporting(E_ALL);

define('VERSION','1.0');

//定义IN_QIT核心常量，所有被加载的文件都需要判断是否已定义该常量（防止站外非法加载）
define('IN_QIT', true);
//框架所在路径的根目录，例如Qit::debug(QIT_ROOT); //打印出：/var/www/php_object
define('QIT_ROOT', substr(dirname(__FILE__), 0, -8));
//框架所在路径，例如Qit::debug(Qit_PATH); //打印出：/var/www/php_object/~myapp
define('QIT_PATH',dirname(__FILE__)); 
//定义是否开启系统核心调试，和普通调试不同，该调试将输出错误细节（如数据库密码等）
define('SYSTEM_CORE_DEBUG', false);


//当PHP执行遇到当前文件中不存在的类时，将尝试调用指定方法进行加载类文件
if(function_exists('spl_autoload_register')) {
	spl_autoload_register(array('Qit', 'autoload'));
} else {
	function __autoload($class) {
		return Qit::autoload($class);
	}
}

//将所有执行异常交由Qit类的handleException方法执行显示
set_exception_handler(array('Qit', 'handleException'));

//开启系统调试，则所有错误及执行失败时，将调用Qit类的handleError和handleShutdown方法
if(SYSTEM_CORE_DEBUG) {
	set_error_handler(array('Qit', 'handleError'));
	register_shutdown_function(array('Qit', 'handleShutdown'));
}
/**
 * Qit框架核心类
 *
 * @author Harold
 * @version $Id: Qit.php 2012-05-13 09:47:11Z Harold $
 * @package Qit
 * @since 1.0
 */
class Qit
{
	
	
	private static $_autoloads;
	private static $_app;
	private static $_lang;
	
	private static $_classFiles = array(
		'db_driver_mysql' => '/db/db_driver_mysql.php',
		'db_driver_mysqli' => '/db/db_driver_mysqli.php',
		'db_driver_pdo' => '/db/db_driver_pdo.php',
		'memory_driver_apc' => '/cache/memory/memory_driver_apc.php',
		'memory_driver_eaccelerator' => '/cache/memory/memory_driver_eaccelerator.php',
		'memory_driver_memcache' => '/cache/memory/memory_driver_memcache.php',
		'memory_driver_redis' => '/cache/memory/memory_driver_redis.php',
		'memory_driver_xcache' => '/cache/memory/memory_driver_xcache.php',
	);
	
	/**
	 * 获取应用实例
	 */
	public static function app(){
		if(!self::$_app){
			self::createapp();
		} 
		return self::$_app;		
	}
	
	/**
	 * 创建应用实例 
	 * @return Qit_application::instance();
	 */
	public static function createapp(){	 	
		if(!is_object(self::$_app)){
			self::$_app = Qit_application::instance();
		}
		return self::$_app;
	}
	
	/**
	 * 自定义处理异常消息
	 * @param string $exception 异常消息
	 */
	public static function handleException($exception) {
		Qit_error::exception_error($exception); 
	}
	
	/**
	 * 使用自定义类函数显示PHP结束执行信息
	 */
	public static function handleShutdown() {
		if(($error = error_get_last()) && $error['type'] & SYSTEM_DEBUG) {
			Qit_error::system_error($error['message'], true, true, false);
		}
	} 

	/**
	 * 自定义处理系统错误消息
	 * @param int $errno 出错ID号
	 * @param string $errstr 出错消息
	 * @param string $errfile 出错文件名称
	 * @param int $errline 出错文件所在行号
	 */
	public static function handleError($errno, $errstr, $errfile, $errline) {
		if($errno & SYSTEM_CORE_DEBUG) {
			Qit_error::system_error($errstr, true, true, false);
		}
	}
	
	/**
	 * 加载应用语言项
	 * @param string $file 语言项所在文件
	 * @param string $langvar 语言项键值
	 * @param array $vars 语言中待替换的变量
	 * @param string $default 若不存在该语言项，默认替换值
	 * @return mixed
	 */
	public static function t($file, $langvar, $vars = array(), $default = null) {				
		list($path, $file) = explode('/', $file);
		if(!$file) {
			$file = $path;
			$path = '';
		}
	 
		$key = $file . '_' . $langvar;
		
		if(isset(self::$_lang[$key][$langvar])){
			return self::$_lang[$key][$langvar];
		}
		
		$lang_local = self::app()->config('language/local');
		
		$lang = array();
		$core_lang = array();
		
		$core_file = self::app()->getbasePath() . '/language/'.$lang_local.'/lang.php';
		
		if(is_file($core_file)){
			$core_lang = include @$core_file;
		}
		
		$lang_file = self::app()->getbasePath() . '/language/' .$lang_local . '/' . ($path == '' ? '' : $path . '/') . $file . '.php';
		if(is_file($lang_file)){
			$lang = include  $lang_file;
		}
		self::$_lang = array_merge($core_lang,$lang);	
		$returnvalue[$key] = self::$_lang;
		   
		$return = $langvar !== null ? (isset($returnvalue[$key][$langvar]) ? $returnvalue[$key][$langvar] : null) : $returnvalue[$key];
		
		$return = $return === null ? ($default !== null ? $default : $langvar) : $return;
		$searchs = $replaces = array();
		if($vars && is_array($vars)) {
			foreach($vars as $k => $v) {
				$searchs[] = '{'.$k.'}';
				$replaces[] = $v;
			}
		}
		$return = str_replace($searchs, $replaces, $return);
			
		return $return;
	}
	
	/**
	 * 框架运行时自动加载加载所需的类
	 * @return boolean
	 */
	public static function autoload($class) {		
		if(class_exists($class)){
			return true;
		}
		$class = strtolower($class); 
		
		if (strpos ( $class, strtolower('Qit_') ) !== false) {
			list($folder,$file) = explode('_',$class,2);
			//当类命中包含Qit时，将在框架libraries文件夹下查找该类。例如类名为Qit_error，将查找libraries/error.php文件
			if (strpos ( $class, strtolower('Qit_') ) !== false) {
				$filepath = QIT_PATH . '/libraries/' . $file . '.php';
			} 
		}else{			
			$filepath = QIT_PATH . self::$_classFiles[$class]; 
		}
		try {
			$key = md5($filepath);
			// 未加载文件则尝试加载
			if (! isset ( self::$_autoloads[$key] )) {				
				if (is_file ( $filepath )) {
					self::$_autoloads [$key] = true;
					return include $filepath;
				} else {
					throw new Exception ( 'System file lost:' . $filepath );
				}
			}
			return true;
	
		} catch (Exception $exc) { 
			$trace = $exc->getTrace();
			foreach ($trace as $log) {
				if(empty($log['class']) && $log['function'] == 'class_exists') {
					return false;
				}
			}
			Qit_error::exception_error($exc);
		}
	} 
	
	public static function debug($var = null, $vardump = false) {
		echo '<pre>';
		$vardump = $var === null ? true : $vardump;
		if($vardump) {
			var_dump($var);
		} else {
			print_r($var);
		}
		exit();
	}
}
