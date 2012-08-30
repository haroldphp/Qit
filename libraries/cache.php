<?php
class Qit_cache extends Qit_base
{
	public function init($type,$options){
		$cache_path = DATA_PATH . '/';
		#$class_name =  
		return new cache_file($options);
		  
	}
	
}