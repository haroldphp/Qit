<?php
if(!defined('IN_QIT')) {
	exit('Access Denied');
}
class Qit_html
{
	
	public static function buildLink($content='',$options=array()){
		return self::openTag('a',$options).$content.self::closeTag('a');
		
	}
	
	public static function encode($text)
	{
		return htmlspecialchars($text,ENT_QUOTES,Qit::app()->config('charset'));
	}
	
	public static function tag($tag, $htmlOptions = array(), $content = false, $closeTag = true) {
		$html = '<' . $tag . self::renderAttributes ( $htmlOptions );
		if ($content === false)
			return $closeTag ? $html . ' />' : $html . '>';
		else
			return $closeTag ? $html . '>' . $content . '</' . $tag . '>' : $html . '>' . $content;
	}
	
	public static function openTag($tag,$htmlOptions=array())
	{
		return '<' . $tag . self::renderAttributes($htmlOptions) . '>';
	}
	
	 
	public static function closeTag($tagname)
	{
		return '</'.$tagname.'>';
	}
	
	public static function renderAttributes($htmlOptions)
	{
		static $specialAttributes=array(
				'checked'=>1,
				'declare'=>1,
				'defer'=>1,
				'disabled'=>1,
				'ismap'=>1,
				'multiple'=>1,
				'nohref'=>1,
				'noresize'=>1,
				'readonly'=>1,
				'selected'=>1,
		);
	
		if($htmlOptions===array())
			return '';
	
		$html='';
		if(isset($htmlOptions['encode']))
		{
			$raw=!$htmlOptions['encode'];
			unset($htmlOptions['encode']);
		}
		else
			$raw=false;
	
		if($raw)
		{
			foreach($htmlOptions as $name=>$value)
			{
				if(isset($specialAttributes[$name]))
				{
					if($value)
						$html .= ' ' . $name . '="' . $name . '"';
				}
				else if($value!==null)
					$html .= ' ' . $name . '="' . $value . '"';
			}
		}
		else
		{
			foreach($htmlOptions as $name=>$value)
			{
				if(isset($specialAttributes[$name]))
				{
					if($value)
						$html .= ' ' . $name . '="' . $name . '"';
				}
				else if($value!==null)
					$html .= ' ' . $name . '="' . self::encode($value) . '"';
			}
		}
		return $html;
	}
	 
	
}