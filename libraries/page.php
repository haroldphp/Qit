<?php

if(!defined('IN_QIT')) {
	exit('Access Denied');
}
/**
 * Qit框架分页类
 *
 * @author Harold
 * @version $Id: Qit.php 2012-07-17 10:11:52Z Harold $
 * @package libraries
 * @since 1.0
 */
class Qit_Page extends Qit_base {
	// 起始行数
	public $firstRow	;
	// 列表每页显示行数
	public $listRows	;
	// 页数跳转时要带的参数
	public $parameter  ;
	//
	public $wraptag = '';
	//是否是ajax分页
	public $isAjax = false;
	//ajax分页执行的Javascript代码
	public $ajaxCode = '';
	// 分页总页面数
	protected $totalPages  ;
	// 总行数
	protected $totalRows  ;
	// 当前页数
	protected $nowPage    ;
	// 分页的栏的总页数
	protected $coolPages   ;
	// 分页栏每页显示的页数
	protected $rollPage   ;
	// 分页显示定制
	protected $config  =	array('header'=>'条记录','prev'=>'上一页','next'=>'下一页','first'=>'第一页','last'=>'最后一页','theme'=>' %totalRow% %header% %nowPage%/%totalPage% 页 %upPage% %downPage% %first%  %prePage%  %linkPage%  %nextPage% %end%');

	/**
	 * 架构函数	 
	 * @access public	 
	 * @param array $totalRows  总的记录数
	 * @param array $listRows  每页显示记录数
	 * @param array $parameter  分页跳转的参数
	 
	*/
	public function __construct($totalRows,$listRows='',$parameter='') {
		$this->totalRows = $totalRows;
		$this->parameter = $parameter;
		$this->rollPage = 5;
		$this->listRows = !empty($listRows)?intval($listRows):Qit::app()->config('page/page_size');
		$this->totalPages = ceil($this->totalRows/$this->listRows);     //总页数
		$this->coolPages  = ceil($this->totalPages/$this->rollPage);
		$page_var = Qit::app()->config('page/page_var');
		$this->nowPage  = !empty($_GET[$page_var])?intval($_GET[$page_var]):1;
		if(!empty($this->totalPages) && $this->nowPage>$this->totalPages) {
			$this->nowPage = $this->totalPages;
		}
		$this->firstRow = $this->listRows*($this->nowPage-1);
	}
	
	/**
	 * 自定义分页显示配置
	 * @param string $name 配置名
	 * @param string $value 配置值
	 */
	public function setConfig($name,$value) {
		if(isset($this->config[$name])) {
			$this->config[$name]    =   $value;
		}
	}
	
	public function ajaxUrl($jscode,$p){
		return " onclick='$jscode($p)' ";
	}
	
	/**
	 * 设置分页链接内部标签
	 * @param string $value
	 * @return 返回附带标签的链接
	 */
	protected function wraptag($value){
		if($this->wraptag){
			return '<'.$this->wraptag . '>' . $value . '</'. $this->wraptag .'>';
		}else{
			return $value;
		}
	}

	/**	 
	 * 分页显示输出	 
	 * @access public	 
	 */
	public function show() {
		if(0 == $this->totalRows) return '';
		$p = Qit::app()->config('page/page_var'); 
		$nowCoolPage      = ceil($this->nowPage/$this->rollPage);
		$url  =  $_SERVER['REQUEST_URI'].(strpos($_SERVER['REQUEST_URI'],'?')?'':"?").$this->parameter;
		$parse = parse_url($url);
		if(isset($parse['query'])) {
			parse_str($parse['query'],$params);
			unset($params[$p]);
			$url   =  $parse['path'].'?'.http_build_query($params);
		}
		//上下翻页字符串
		$upRow   = $this->nowPage-1;
		$downRow = $this->nowPage+1;
		if ($upRow>0){
 			$upPage= ($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,$upRow)  : "<a href='".$url."&".$p."=$upRow'"). ">".$this->wraptag($this->config['prev'])."</a>";
		}else{
			$upPage="";
		}

		if ($downRow <= $this->totalPages){
			$downPage=($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,$downRow)  : "<a href='".$url."&".$p."=$downRow'").">".$this->wraptag($this->config['next'])."</a>";
		}else{
			$downPage="";
		}
		// << < > >>
		if($nowCoolPage == 1){
			$theFirst = "";
			$prePage = "";
		}else{
			$preRow =  $this->nowPage-$this->rollPage;
			$prePage = ($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,$preRow)  : "<a href='".$url."&".$p."=$preRow'"). ">".$this->wraptag("上".$this->rollPage."页")."</a>";
			$theFirst = ($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,1)  : "<a href='".$url."&".$p."=1'"). ">".$this->wraptag($this->config['first'])."</a>";
		}
		if($nowCoolPage == $this->coolPages){
			$nextPage = "";
			$theEnd="";
		}else{
			$nextRow = $this->nowPage+$this->rollPage;
			$theEndRow = $this->totalPages;
			$nextPage = ($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,$nextRow)  : "<a href='".$url."&".$p."=$nextRow'"). ">".$this->wraptag("下".$this->rollPage."页")."</a>";
			$theEnd = ($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,$theEndRow)  : "<a href='".$url."&".$p."=$theEndRow'"). ">".$this->wraptag($this->config['last'])."</a>";
		}
		// 1 2 3 4 5
		$linkPage = "";
		for($i=1;$i<=$this->rollPage;$i++){
			$page=($nowCoolPage-1)*$this->rollPage+$i;
			if($page!=$this->nowPage){
				if($page<=$this->totalPages){
					$linkPage .= ($this->isAjax ? "<a href='#'".$this->ajaxUrl($this->ajaxCode,$page)  : "<a href='".$url."&".$p."=$page'"). ">".$this->wraptag($page)."</a>";
				}else{
					break;
				}
			}else{
				if($this->totalPages != 1){
					$linkPage .= "<a class='select'>".$this->wraptag($page)."</a>";
				}
			}
		}
		$pageStr	 =	 str_replace(
				array('%header%','%nowPage%','%totalRow%','%totalPage%','%upPage%','%downPage%','%first%','%prePage%','%linkPage%','%nextPage%','%end%'),
				array($this->config['header'],$this->nowPage,$this->totalRows,$this->totalPages,$upPage,$downPage,$theFirst,$prePage,$linkPage,$nextPage,$theEnd),$this->config['theme']);
		return $pageStr;
	}

}