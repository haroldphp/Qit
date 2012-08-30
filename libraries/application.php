<?php

if(!defined('IN_QIT')) {
	exit('Access Denied');
}

/**
 * Qit应用实例类
 *
 * @author Harold
 * @version $Id: class_core.php 2012-07-12 14:12:22Z Harold $
 * @package class
 * @since 1.0
 */

class Qit_application
{ 


	public $session = null; 
	public $var = array();
	public $db = null;
	
	protected $_basePath;
	protected $_controllerPath;	
	protected $_modelPath;
	protected $_viewPath;
	protected $_systemViewPath;
	protected $_layoutPath;
	

	private static $_controller = '';
	private static $_action = '';	
	private static $_config = array();
	private static $_object = null;
	private static $params = array(); 
	private static $_memory;
	private static $_cache;
	private static $_db_driver = array(
		'mysql' => 'db_driver_mysql',
		'mysqli' => 'db_driver_mysqli',
		'pdo' => 'db_driver_pdo',
	);
	
	public $superglobal = array(
			'GLOBALS' => 1,
			'_GET' => 1,
			'_POST' => 1,
			'_REQUEST' => 1,
			'_COOKIE' => 1,
			'_SERVER' => 1,
			'_ENV' => 1,
			'_FILES' => 1,
	);
	
	protected $initated = false;

	/**
	 * 获取应用实例
	 * @return Qit_application::$_object
	 */
	public static function &instance(){
		if(empty(self::$_object)) {
			self::$_object = new self();
		}
		return self::$_object;
	}
	
	public function __construct() {
	}

	/**
	 * 执行类初始化函数，该函数将初始化系统环境、加载应用配置、输入及输出配置
	 */	 
	public function init() {
		if(!$this->initated){
			$this->_init_env();
			$this->_init_config();
			$this->_init_input();
			$this->_init_output();
			$this->_init_db ();		
		}
		$this->initated = true;
	}
	
	/**
	 * 返回指定单位大小的比特值，例如10m，返回10485760比特
	 * @param string $val 包含“g”、“m”、“k”单位格式的比特大小
	 */
	public function return_bytes($val) {
		$val = trim($val);
		$last = strtolower($val{strlen($val)-1});
		switch($last) {
			case 'g': $val *= 1024;
			case 'm': $val *= 1024;
			case 'k': $val *= 1024;
		}
		return $val;
	}
	
	/**
	 * 检测是否为机器人访问（即发帖机等应用程序）
	 * @param string $useragent 浏览器用于 HTTP请求的用户代理头的值
	 */
	public function checkrobot($useragent = '') {
		static $kw_spiders = array('bot', 'crawl', 'spider' ,'slurp', 'sohu-search', 'lycos', 'robozilla');
		static $kw_browsers = array('msie', 'netscape', 'opera', 'konqueror', 'mozilla');
	
		$useragent = strtolower(empty($useragent) ? $_SERVER['HTTP_USER_AGENT'] : $useragent);
		if(strpos($useragent, 'http://') === false && $this->array_pos($useragent, $kw_browsers)) return false;
		if(self::array_pos($useragent, $kw_spiders)) return true;
		return false;
	}
	
	/**
	 * 生成随机数
	 * @param int $length 随机数长度
	 * @param int $numeric 是否生成纯数字随机数
	 * @return string 返回已生成的随机数
	 */
	public function random($length, $numeric = 0) {
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed{mt_rand(0, $max)};
		}
		return $hash;
	}
	/**
	 * 检测字符串中是否存在指定字符
	 * @param string|array $string 待查询的字符串
	 * @param string|array $arr 包含的字符串
	 * @param int|boolean $returnvalue 是否返回包含的字符所在位数
	 */
	public function array_pos($string, &$arr, $returnvalue = false) {
		if(empty($string)) return false;
		foreach((array)$arr as $v) {
			if(strpos($string, $v) !== false) {
				$return = $returnvalue ? $v : true;
				return $return;
			}
		}
		return false;
	}
	
	/**
	 * 加载应用语言项
	 * @param string $file
	 * @param string $langvar
	 * @param array $vars
	 * @param string $default
	 * @return mixed
	 */
	public static function lang($file, $langvar = null, $vars = array(), $default = null) {
		return Qit::t($file, $langvar, $vars, $default);
	}
	
	/**
	 * 获取应用项目静态文件地址（即CSS、images、javascript等）
	 * @return string
	 */
	public function staticUrl(){
		return dirname(rtrim($_SERVER['SCRIPT_NAME'],'/')) . '/static';
	}
	
	/**
	 * 去除数组或字符串中被转义字符的反斜线
	 * @param array|string $string 待去除转义符的数组（包含字符串的数组）或字符串
	 * @return string
	 */
	public function deep_stripslashes($string) {
		if(is_array($string)) {
			$keys = array_keys($string);
			foreach($keys as $key) {
				$val = $string[$key];
				unset($string[$key]);
				$string[addslashes($key)] = $this->deep_stripslashes($val);
			}
		} else {
			$string = stripslashes($string);
		}
		return $string;
	}
	
	/**
	 * 对数组或字符串中需要被转义字符添加反斜线
	 * @param array|string $string 需要添加转义符的数组（包含字符串的数组）或字符串
	 * @return string
	 */
	public function deep_addslashes($string) {
		if(is_array($string)) {
			$keys = array_keys($string);
			foreach($keys as $key) {
				$val = $string[$key];
				unset($string[$key]);
				$string[addslashes($key)] = $this->deep_addslashes($val);
			}
		} else {
			$string = addslashes($string);
		}
		return $string;
	}
	
	public function setglobal($key , $value, $group = null) {		
		$key = explode('/', $group === null ? $key : $group.'/'.$key);
		$p = &$this->var;
		foreach ($key as $k) {
			if(!isset($p[$k]) || !is_array($p[$k])) {
				$p[$k] = array();
			}
			$p = &$p[$k];
		}
		$p = $value;
		return true;
	}
	
	public function getglobal($key, $group = null) {		
		$key = explode('/', $group === null ? $key : $group.'/'.$key);
		$v = &$this->var; 
		foreach ($key as $k) {
			if (!isset($v[$k])) {
				return null;
			}
			$v = &$v[$k];
		}
		return $v;
	} 
	


	/**
	 * 当在服务器配置使用memcache等内存型缓存，并在配置文件中启用后，调用该函数将进行初始化内存缓存应用并获取缓存实例
	 * @return Qit_memory
	 */
	public function memory() {
		if(!self::$_memory) {
			self::$_memory = new Qit_memory(); 
			self::$_memory->init(self::$_config['memory']);
		}
		return self::$_memory;
	}
	
	/**
	 * 
	 */
	public function cache($type='',$options=array()){
		if(!self::$_cache){
			self::$_cache = new Qit_cache();
			self::$_cache->init($type ? $type : $this->config('cache/type'),$options);			
		}
		return self::$_cache;
	}
	
	/**
	 * 获取当前应用的指定配置
	 * @param string $key
	 * @return string
	 */
	public function config($key,$value=''){
		if(!$value){
			return $this->getglobal($key,'config');
		}else{
			$this->setglobal($key, $value,'config');
		}
	}
	
	/**
	 * 加载控制器、模型、视图文件
	 * @param string $file 文件名称
	 * @param string $type 控制器、模型、视图
	 * @return 文件路径+文件名
	 */
	public function loadFile($file,$type=''){
		static $_loadedFiles = array();
		$path = '';
		$map = array(
				'controller' => 'controllers',
				'model' => 'models',
				'view' => 'views',
		);
		if(!$type){
			$type = 'controller';
		}
		
		$key = md5($file . '_' . $map[$type]);
		
		if(!$_loadedFiles[$key]){
			if($type=='controller'){
				$path = $this->getControllerPath();
			}elseif($type=='model'){
				$path = $this->getModelPath();				
			}else{
				$path = $this->getViewPath();
			}
			$path .=  '/' . $file . '.php';
			
		
			$_loadedFiles[$key] = $path; 
		}
		
		
		return $_loadedFiles[$key];
		
	}
	
	/**
	 * 设置当前时区
	 * @param int $timeoffset 系统所在时区值
	 */
	public function timezone_set($timeoffset = 0) {
		if(function_exists('date_default_timezone_set')) {
			@date_default_timezone_set('Etc/GMT'.($timeoffset > 0 ? '-' : '+').(abs($timeoffset)));
		}
	}
	
	/**
	 * 获取客户端访问时的IP
	 * @return string $ip
	 */
	public function get_client_ip() {
		return Qit_client::getIp(); 
	}
	
	/**
	 * 获取客户端访问的ip所在物理地址
	 * @param string $ip ip地址
	 * @return string
	 */
	public function ipaddress($ip,$type='full') {	
		return Qit_client::get_ip_address($ip,$type);	
	}
	
	/**
	 * 获取当前脚本URL
	 * @return multitype:
	 */
	public function get_script_url() {
		if(!isset($this->var['PHP_SELF'])){
			$scriptName = basename($_SERVER['SCRIPT_FILENAME']);
			if(basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
				$this->var['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
			} else if(basename($_SERVER['PHP_SELF']) === $scriptName) {
				$this->var['PHP_SELF'] = $_SERVER['PHP_SELF'];
			} else if(isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
				$this->var['PHP_SELF'] = $_SERVER['ORIG_SCRIPT_NAME'];
			} else if(($pos = strpos($_SERVER['PHP_SELF'],'/'.$scriptName)) !== false) {
				$this->var['PHP_SELF'] = substr($_SERVER['SCRIPT_NAME'],0,$pos).'/'.$scriptName;
			} else if(isset($_SERVER['DOCUMENT_ROOT']) && strpos($_SERVER['SCRIPT_FILENAME'],$_SERVER['DOCUMENT_ROOT']) === 0) {
				$this->var['PHP_SELF'] = str_replace('\\','/',str_replace($_SERVER['DOCUMENT_ROOT'],'',$_SERVER['SCRIPT_FILENAME']));
				$this->var['PHP_SELF'][0] != '/' && $this->var['PHP_SELF'] = '/'.$this->var['PHP_SELF'];
			} else {
				Qit_error::system_error('request_tainting');
			}
		}
		return $this->var['PHP_SELF'];
	}
	
	/**
	 * 
	 * @param unknown_type $end
	 * @param unknown_type $start
	 * @param unknown_type $dec
	 * @return string
	 */
	public function benchmark($end,$start,$dec=3){
		return number_format(($end-$start),$dec);
	}
	
 	/**
 	 * 转换数据单位
 	 * @param int $size 数据大小值
 	 * @return string
 	 */
	public function convert($size){
		$unit=array('B','KB','MB','GB','TB');
		return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
	}
	
	/**
	 * 运行应用
	 */
	public function run(){
		$this->init();
		//路由转发
		$this->route();		
	}
	
	
	
	/**
	 * 获取当前请求控制器
	 * @return string
	 */
	public function getController(){
		return self::$_controller;
	}
	
	/**
	 * 控制当前执行方法
	 * @return string
	 */
	public function getAction(){
		return self::$_action;
	}
	
	/**
	 * 批量循环生成目录
	 * @param string|array $dirs 目录名称 （数组）
	 * @param int $mode 目录权限
	 */
	public function mkdirs($dirs,$mode=0777){
		foreach ((array) $dirs as $dir){
			if(!is_dir($dir))
				mkdir($dir);
		}
	}
	
	/**
	 * 生成目录安全文件（防止直接访问到目录结构）
	 * @param string|array $dirs 目录名称（数组）
	 * @param string $filename 安全文件名称
	 */
	public function mksafedir($dirs,$filename='index.html'){
		foreach ((array) $dirs as $dir){
			if(is_dir($dir) && !file_exists($dir.'/'.$filename))
				file_put_contents($dir.'/'.$filename, '');
		}
	}
	
	
	/**
	 * 获取URI路由中控制器及执行方法，实例化控制器并执行方法（可以进行扩展及改进，以支持更多不同路由模式）
	 * @throws Exception
	 */
	protected function dispatch(){
		$default_controller = $this->config('route/default_controller');
		$default_action = $this->config('route/default_action');
		if (!isset($_GET['r'])){
			self::$_controller = $default_controller ? $default_controller : 'index';
			self::$_action = $default_action ? $default_action : 'index';
				
		}else{
			$route= $_GET['r'];
			$routeParts = split( "/",$route);
			self::$_controller=$routeParts[0];
			self::$_action=isset($routeParts[1])? $routeParts[1]:"index";
			array_shift($_GET);
			self::$params=$_GET;
		}	

		$path = $this->loadFile(self::$_controller);
		
		if(is_file($path)){
			include $path;
		}else{
			throw new Exception('System could not find controller:'.self::$_controller);
		} 
		$controller_name = ucfirst(self::$_controller).'Controller'; 
		$action_name = self::$_action.'Action';
			
		$app = new $controller_name;
		if(method_exists($app, $action_name)){
			$app->$action_name();
		}else{
			throw new Exception('System could not find action:'.self::$_action);
		}
	
	}
	
	public function getAppPath(){
		return defined('APP_NAME') ? Qit_ROOT . '/' . APP_NAME : dirname($_SERVER['SCRIPT_FILENAME']);
	}
	
	public function getBasePath(){
		return $this->_basePath;
	}
	
	public function setBasePath($path){
		if(($this->_basePath=realpath($path))===false || !is_dir($this->_basePath))
			throw new Exception(Qit::t('qep','Application base path "{path}" is not a valid directory.',
				array('{path}'=>$path)));
	}
	 
	public function getControllerPath()
	{
		if($this->_controllerPath!==null)
			return $this->_controllerPath;
		else
			return $this->_controllerPath=$this->getBasePath().DIRECTORY_SEPARATOR.'controllers';
	}
	
	public function setModelPath($path)
	{
		if(($this->_modelPath=realpath($path))===false || !is_dir($this->_modelPath))
			throw new Exception(Qit::t('qep', 'The model path "{path}" is not a valid directory.',
					array('{path}'=>$path)));
	}
	
	public function getModelPath()
	{
		if($this->_modelPath!==null)
			return $this->_modelPath;
		else
			return $this->_modelPath=$this->getBasePath().DIRECTORY_SEPARATOR.'models';
	}
	
	public function setViewPath($path)
	{
		if(($this->_viewPath=realpath($path))===false || !is_dir($this->_viewPath))
			throw new Exception(Qit::t('qep', 'The view path "{path}" is not a valid directory.',
					array('{path}'=>$path)));
	}
	
	public function getViewPath()
	{
		if($this->_viewPath!==null)
			return $this->_viewPath;
		else
			return $this->_viewPath=$this->getBasePath().DIRECTORY_SEPARATOR.'views';
	}
	
	public function getLayoutPath()
	{
		if($this->_layoutPath!==null)
			return $this->_layoutPath;
		else
			return $this->_layoutPath=$this->getViewPath().DIRECTORY_SEPARATOR.'layouts';
	}
	
	public function setLayoutPath($path)
	{
		if(($this->_layoutPath=realpath($path))===false || !is_dir($this->_layoutPath))
			throw new Exception(Qit::t('Qit', 'The layout path "{path}" is not a valid directory.',
					array('{path}'=>$path)));
	}
	 

	/**
	 * 路由转发
	 */
	protected function route(){
		$this->dispatch();
	}
	
	/**
	 * 自动生成应用目录
	 */
	protected function create_app_dir(){
		if(!is_dir($this->getBasePath())){
			$this->mkdirs($this->getBasePath(),0777);
		}
		$dirs = array();
		
		if(is_writable($this->getBasePath())){ 
			$dirs['controller_path'] = $this->getControllerPath();
			$dirs['model_path'] = $this->getModelPath();
			$dirs['view_path'] = $this->getViewPath();
			$dirs['view_layout_path'] = $this->getLayoutPath();
			$dirs['config_path'] = $this->getBasePath() . '/config';
			$dirs['language_path'] = $this->getBasePath() . '/language';
			$dirs['language_local_path'] = $this->getBasePath() . '/language/'.$this->config('language/local');
			$dirs['data_path'] = $this->getBasePath() . '/data';
			$dirs['errorlog_path'] = $this->getBasePath() . '/errorlog';			
			$dirs['static_path'] = $this->getBasePath() . '/../static';
			$dirs['css_path'] = $dirs['static_path'] .  '/css';
			$dirs['js_path'] = $dirs['static_path'] . '/js';
			$dirs['image_path'] = $dirs['static_path'] . '/images';
			//创建应用基础目
			$this->mkdirs($dirs);
			$this->mksafedir($dirs);
			
			if(!is_file($dirs['config_path'] . '/config.php')){
				file_put_contents($dirs['config_path'] . '/config.php', '<?php return array("配置项"=>"配置值"); ?>');
			}
		}else{
			header("Content-Type:text/html; charset=utf-8");
        	exit('<div style=\'font-weight:bold;float:left;width:345px;text-align:center;border:1px solid silver;background:#E8EFFF;padding:8px;color:red;font-size:14px;font-family:Tahoma\'>项目目录不可写，目录无法自动生成！<BR>请设置目录权限或手动生成项目目录~</div>');
		}
		
	}
		
	
	/**
	 * 初始化系统环境
	 */
	protected function _init_env() {	
		error_reporting(E_ERROR);
		
		if(PHP_VERSION < '5.3.0') {
			set_magic_quotes_runtime(0);
		}
		define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());
		define('ICONV_ENABLE', function_exists('iconv'));
		define('MB_ENABLE', function_exists('mb_convert_encoding'));
		define('EXT_OBGZIP', function_exists('ob_gzhandler'));
	
		define('TIMESTAMP', time());
		$this->timezone_set(8); 
	
		if(function_exists('ini_get')) {
			$memorylimit = @ini_get('memory_limit');
			if($memorylimit && $this->return_bytes($memorylimit) < 33554432 && function_exists('ini_set')) {
				ini_set('memory_limit', '128m');
			}
		}
	
		define('IS_ROBOT', $this->checkrobot()); 
		
		//过滤非法超级全局变量
		foreach ($GLOBALS as $key => $value) {
			if (!isset($this->superglobal[$key])) {
				$GLOBALS[$key] = null; 
				unset($GLOBALS[$key]);
			}
		} 
		$this->var = array(
				'sid' => '',
				'formhash' => '',
				'timestamp' => TIMESTAMP,
				'starttime' => START_TIME,
				'clientip' => $this->get_client_ip(),
				'referer' => '',
				'charset' => '',
				'gzipcompress' => '',
				'authkey' => '',
				'timenow' => array(),
				'widthauto' => 0,
				'disabledwidthauto' => 0,
	
				'PHP_SELF' => '',
				'siteurl' => '',
				'siteroot' => '',
				'siteport' => '',
	
				'config' => array(),
				'setting' => array(),
				'cookie' => array(),
				'style' => array(),
				'cache' => array(),
				'session' => array(),	
				'mobile' => '',
	
		);
		$this->var['PHP_SELF'] = htmlspecialchars($this->get_script_url());
		$this->var['basescript'] = CURSCRIPT;
		$this->var['basefilename'] = basename($this->var['PHP_SELF']);
		$sitepath = substr($this->var['PHP_SELF'], 0, strrpos($this->var['PHP_SELF'], '/'));
		if(defined('IN_API')) {
			$sitepath = preg_replace("/\/api\/?.*?$/i", '', $sitepath);
		} elseif(defined('IN_ARCHIVER')) {
			$sitepath = preg_replace("/\/archiver/i", '', $sitepath);
		}
		$this->var['siteurl'] = htmlspecialchars('http://'.$_SERVER['HTTP_HOST'].$sitepath.'/');
	
		$url = parse_url($this->var['siteurl']);
		$this->var['siteroot'] = isset($url['path']) ? $url['path'] : '';
		$this->var['siteport'] = empty($_SERVER['SERVER_PORT']) || $_SERVER['SERVER_PORT'] == '80' ? '' : ':'.$_SERVER['SERVER_PORT']; 
	  
	
	}
	
	/**
	 * 多维数组的合并(相同的字符串键名，后面的覆盖前面的)
	 * @param array $array1
	 * @param array $array2
	 */
	public  function multi_array_merge($array1,$array2){
		if (is_array($array2) && count($array2)){//不是空数组的话
			foreach ($array2 as $k=>$v){
				if (is_array($v) && count($v)){
					$array1[$k] = $this->multi_array_merge($array1[$k], $v);
				}else {
					if (!empty($v)){
						$array1[$k] = $v;
					}
				}
			}
		}else {
			$array1 = $array2;
		}
		return $array1;
	}

	/**
	 * 判断访问请求地址中是否存在xss注入非法字符
	 * @return boolean
	 */
	protected function _xss_check() {
		$temp = strtoupper(urldecode(urldecode($_SERVER['REQUEST_URI'])));
		if(strpos($temp, '<') !== false || strpos($temp, '"') !== false || strpos($temp, 'CONTENT-TRANSFER-ENCODING') !== false) {
			Qit_error::system_error('request_tainting');
		}
		return true;
	}
	
	
	/**
	 * 初始化并过滤系统接收到的数据、此函数将对超级全局变量中的数据进行过滤
	 */
	protected function _init_input() {
		if (isset($_GET['GLOBALS']) ||isset($_POST['GLOBALS']) ||  isset($_COOKIE['GLOBALS']) || isset($_FILES['GLOBALS'])) {
			Qit_error::system_error('request_tainting');
		}
	
		if(MAGIC_QUOTES_GPC) {
			$_GET = $this->array_stripslashes($_GET);
			$_POST = $this->array_stripslashes($_POST); 
			$_COOKIE = $this->array_stripslashes($_COOKIE);
		}
	
		$prelength = strlen($this->config('cookie/cookiepre'));
		foreach($_COOKIE as $key => $val) {
			if(substr($key, 0, $prelength) == $this->config('cookie/cookiepre')) {
				$this->var['cookie'][substr($key, $prelength)] = $val;
			}
		}
	
	
		if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
			$_GET = array_merge($_GET, $_POST);
		}
	
	
		if(!empty($this->var['config']['input']['compatible'])) {
			foreach($_GET as $k => $v) {
				$this->var['gp_'.$k] = self::array_addslashes($v);
			}
		}
	
		if(isset($_GET['page'])) {
			$_GET['page'] = rawurlencode($_GET['page']);
		}
	
		$this->var['mod'] = empty($_GET['mod']) ? '' : htmlspecialchars($_GET['mod']);
		$this->var['inajax'] = empty($_GET['inajax']) ? 0 : (empty($this->var['config']['output']['ajaxvalidate']) ? 1 : ($_SERVER['REQUEST_METHOD'] == 'GET' && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || $_SERVER['REQUEST_METHOD'] == 'POST' ? 1 : 0));
		$this->var['page'] = empty($_GET['page']) ? 1 : max(1, intval($_GET['page']));
		$this->var['sid'] = $this->var['cookie']['sid'] = isset($this->var['cookie']['sid']) ? htmlspecialchars($this->var['cookie']['sid']) : '';
		$this->var['handlekey'] = !empty($_GET['handlekey']) && preg_match('/^\w+$/', $_GET['handlekey']) ? $_GET['handlekey'] : '';
	
		if(empty($this->var['cookie']['saltkey'])) {
			$this->var['cookie']['saltkey'] = $this->random(8); 
		} 
		$this->var['authkey'] = md5($this->var['config']['security']['authkey'].$this->var['cookie']['saltkey']);
	
	}
	
	/**
	 * 初始化并加载应用配置
	 */
	protected function _init_config() {
		//获取默认的应用配置
		$_default_config = @include QIT_PATH . '/common/config_default.php' ;		 
		
		if($_app_config = @include $this->getAppPath().'/protected/config/config.php'){
			//合并配置
			self::$_config = $this->multi_array_merge($_default_config, $_app_config);
		}		 

		//合并配置
		if(!self::$_config['basePath']){
			throw new Exception('Application base path should be defined');
		}else{
			$this->setBasePath(self::$_config['basePath']);
		}   
	
		if(empty(self::$_config['security']['authkey'])) {
			self::$_config['security']['authkey'] = md5(self::$_config['cookie']['cookiepre'].self::$_config['db'][1]['dbname']);
		}
 
		if(empty(self::$_config['debug'])) { 
			define('SYSTEM_DEBUG', false);
			error_reporting(0);
		} elseif(self::$_config['debug'] === 1 || self::$_config['debug'] === 2 || !empty($_REQUEST['debug']) && $_REQUEST['debug'] === self::$_config['debug']) {
			define('SYSTEM_DEBUG', true);
			error_reporting(E_ERROR);
			if(self::$_config['debug'] === 2) {
				error_reporting(E_ALL);
			}
		} else {
			define('SYSTEM_DEBUG', false);
			error_reporting(0);
		} 
		
		//加载基础控制器，应用项目中所有控制器均需继承该控制器
		if(self::$_config['baseController']){
			include_once $this->loadFile(self::$_config['baseController']);
		}
			 
		$this->var['config'] = & self::$_config; 

		$this->create_app_dir();
	
		if(substr(self::$_config['cookie']['cookiepath'], 0, 1) != '/') {
			$this->var['config']['cookie']['cookiepath'] = '/'.$this->var['config']['cookie']['cookiepath'];
		}
		$this->var['config']['cookie']['cookiepre'] = $this->var['config']['cookie']['cookiepre'].substr(md5($this->var['config']['cookie']['cookiepath'].'|'.$this->var['config']['cookie']['cookiedomain']), 0, 4).'_';		

	}
	
	/**
	 * 初始化并过滤系统数据输出
	 */
	protected function _init_output() { 
	
		if($this->config('security/urlxssdefend') && $_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_SERVER['REQUEST_URI'])) {
			$this->_xss_check();
		}
	
		/* if($this->config['security']['attackevasive'] && (!defined('CURSCRIPT') || !in_array($this->var['mod'], array('seccode', 'secqaa', 'swfupload')) && !defined('DISABLEDEFENSE'))) {
			require_once libfile('misc/security', 'include');
		} */
	
		if(!empty($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') === false) {
			$this->config('output/gzip',false);
		}
	
		$allowgzip = $this->config('output/gzip') && empty($this->var['inajax']) && $this->var['mod'] != 'attachment' && EXT_OBGZIP;
		
		$this->setglobal('gzipcompress', $allowgzip);
		ob_start($allowgzip ? 'ob_gzhandler' : null);
	
		$this->setglobal('charset', $this->config('output/charset'));
		define('CHARSET', $this->config('output/charset'));
		if($this->config('output/forceheader')) {
			@header('Content-Type: text/html; charset='.CHARSET);
		}	
	}
	
	/**
	 * 截取子字符
	 * @param string $string 待截取的字符串
	 * @param int $length 截取长度
	 * @param string $dot 截取连接后缀
	 * @return string
	 */
	public function cutstr($string, $length, $dot = ' ...') {
		if(strlen($string) <= $length) {
			return $string;
		}
	
		$pre = chr(1);
		$end = chr(1);
		$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), $string);
	
		$strcut = '';
		if(strtolower(CHARSET) == 'utf-8') {
	
			$n = $tn = $noc = 0;
			while($n < strlen($string)) {
	
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1; $n++; $noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2; $n += 2; $noc += 2;
				} elseif(224 <= $t && $t <= 239) {
					$tn = 3; $n += 3; $noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4; $n += 4; $noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5; $n += 5; $noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6; $n += 6; $noc += 2;
				} else {
					$n++;
				}
	
				if($noc >= $length) {
					break;
				}
	
			}
			if($noc > $length) {
				$n -= $tn;
			}
	
			$strcut = substr($string, 0, $n);
	
		} else {
			for($i = 0; $i < $length; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			}
		}
	
		$strcut = str_replace(array($pre.'&'.$end, $pre.'"'.$end, $pre.'<'.$end, $pre.'>'.$end), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);
	
		$pos = strrpos($strcut, chr(1));
		if($pos !== false) {
			$strcut = substr($strcut,0,$pos);
		}
		return $strcut.$dot;
	}
	
	
	
	/**
	 * 初始化数据库
	 */
	protected function _init_db() {
		$config_driver = self::$_config['db']['driver'];
		
		$driver = self::$_db_driver[$config_driver];		
		
		Qit_model::init($driver, self::$_config['db']);

		if(!$this->db){
			$this->db = Qit_model::object();
		}
	}
	
	
}