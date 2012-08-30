<?php
if(!defined('IN_QIT')) {
	exit('Access Denied');
}

class Qit_view extends Qit_base
{
	protected static $_instance;
	
	public $vars=array();
	public $layout = '';
	public $templateFile = '';
	public $tplpath = '';
	public $tplsuffix = '';
	
	public function __construct(){
		$this->tplpath = Qit::app()->getViewPath();
		$this->tplsuffix = Qit::app()->config('template/suffix');
		
	}
	

	public function assign($name,$value=''){
		if(is_array($name)) {
			$this->vars   =  array_merge($this->vars,$name);
		}elseif(is_object($name)){
			foreach($name as $key =>$val)
				$this->vars[$key] = $val;
		}else {
			$this->vars[$name] = $value;
		}
	}
	
	public static function getInstance(){
		if(!self::$_instance){
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	public function display($templateFile='',$charset='',$contentType=''){
		$this->fetch($templateFile,$charset,$contentType,true);
		
	}
	
	public function fetch($templateFile='',$charset='',$contentType='',$display=true){
		if(empty($charset))  $charset = Qit::app()->config('output/charset');
		if(empty($contentType)) $contentType = Qit::app()->config('output/contentType'); 
		header("Content-Type:".$contentType."; charset=".$charset);
		header("Cache-control: private");  //支持页面回跳
		header("X-Powered-By:Qit ".VERSION); 
		//页面缓存
		ob_start();
		ob_implicit_flush(0); 
		if(!$templateFile){
			$this->templateFile = $this->tplpath . '/' . Qit::app()->getController() . '/' . Qit::app()->getAction() . $this->tplsuffix;			
		}else{
			$this->templateFile = $this->tplpath . '/' . $templateFile . $this->tplsuffix;
		} 
		if(!file_exists($this->templateFile)){
			throw new Exception('Template File not exists: '.$this->templateFile);
		}
		extract($this->vars, EXTR_OVERWRITE);
		include $this->templateFile;
		$content = ob_get_clean();
		if($display){
			return	$this->output($content,$display);
		}else{
			//获取视图内容
		}	
	}
	
	protected function output($content){		
		echo $content;
	}
	
	public function layout($file,$charset=''){
		$filepath = 'layouts/'.$file;		
		echo $this->fetch($filepath);
	}

}