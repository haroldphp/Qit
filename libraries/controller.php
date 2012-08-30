<?php

if(!defined('IN_QIT')) {
	exit('Access Denied');
}

/**
 * Qit框架控制器类
 *
 * @author Harold
 * @version $Id: Qit.php 2012-07-17 10:11:52Z Harold $
 * @package libraries
 * @since 1.0
 */
class Qit_controller 
{ 
	public static $model=NULL; 
	public static $view = NULL;
	
	public $renderView = true;
	
	public function __construct(){ 
		$this->actions();
	}
	
	/**
	 * 控制器初始实例化时所需执行的代码
	 */
	public function actions(){
		if($this->renderView){
			self::$view = self::_loadView();
		}
	}
	  
	/**
	 * 获取模型实例 
	 * @param string $modelname 模型名称（默认加载和控制器同名模型）
	 * @param string $appname 应用名称（跨应用调用模型）
	 * @return 模型实例
	 */
	public function model($modelname='',$appname=''){
		if(!$modelname){
			$modelname = Qit::app()->getController();
		}
		if(!self::$model){
			self::$model = $this->_loadModel($modelname);
		}
		return self::$model;
	} 
	
	/**
	 * 赋值视图变量
	 * @param string $var 变量名称
	 * @param mixed $value 变量值
	 */
	public function assign($var,$value){
		if(self::$view)
			self::$view->assign($var,$value);
	}

	/**
	 * 渲染视图
	 * @param string $file 视图名称
	 */
	public function display($file='')
	{ 
		self::$view->display($file);
	}
	
	/**
	 * 
	 */
	public function showpage($type,$msg){ 
		$filepath = Qit::app()->getbasePath().'/'.$type.Qit::app()->config('tplsuffix');
		
		echo $filepath;
		if(is_file($filepath)){
			echo include $filepath;
		}
		 
	}
	
	/**
	 * 加载数据模型
	 * @param string $modelname 模型名称
	 * @return 模型实例
	 */
	protected  function _loadModel($modelname){
		$file = Qit::app()->loadFile($modelname,'model');
		if(file_exists($file)){
			include $file;
		}else{
			throw new Exception('Model could not find: ' . $this->getController());
		}
		 
		$modelclass = ucfirst($modelname.'Model');
		
		return new $modelclass;
	}
	
	/**
	 * 加载视图
	 * @return 返回视图实例
	 */
	protected static function _loadView(){
		if(!self::$view){
			self::$view = Qit_view::getInstance();
		}
		return self::$view;		
	} 
	
	/**
	 * 操作错误提示
	 * @param string $lang 语言键值
	 * @param boolean $return 返回错误或直接输出错误
	 * @return mixed
	 */
	public function error($lang,$return=false){
		$msg = Qit::t(Qit::app()->getController(),$lang);
		if($return)
			return $msg;
		else
			$this->showpage('error',$msg);
	}
	
	/**
	 * 操作成功提示
	 * @param string $lang 语言键值
	 * @param boolean $return 返回错误或直接输出错误
	 * @return mixed
	 */
	public function success($lang,$return=false){
		$msg = Qit::t(Qit::app()->getController(),$lang);
		if($return)
			return $msg;
		else
			$this->showpage('success',$msg);
	}
	

	/**
	 * 获取并过滤请求传输数据（防止提交xss注入等非法字符）
	 * @param string $var 数据名
	 * @return 请求数据值
	 */
	public function request($var) {
		$request = isset ( $_REQUEST [$var] ) ? $_REQUEST [$var] : '';
		if (is_array ( $request )) {
			foreach ( $request as $k => $v )
				$var [$k] = htmlspecialchars ( $v ); // 进行过滤
		} else {
			$request = htmlspecialchars ( $request );
		}
		return $request;
	}
	
	
}