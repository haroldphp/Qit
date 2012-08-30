<?php
if (! defined ( 'IN_QIT' )) {
	exit ( 'Access Denied' );
}

return array (
		'basePath' => '', 
		'baseController' => '',
		'cache' => array (
				'type' => 'file' 
		),
		'db' => array (
				'driver' => 'mysql',
				1 => array (	
						'dbhost' => 'localhost',
						'dbuser' => 'user',
						'dbpw' => '',
						'dbcharset' => 'utf8',
						'pconnect' => '0',
						'dbname' => '',
						'tablepre' => 'pre_',
				),
		),
		'output' => array (
				'charset' => 'utf-8',
				'contentType' => 'text/html',
				'forceheader' => 1,
				'gzip' => '0',
				'tplrefresh' => 1,
				'staticurl' => 'static/',
				'ajaxvalidate' => '0',
				'iecompatible' => '0' 
		),
		'language'=>array(
				'local'=>'zh_cn',
		),
		'template' => array (
				'suffix' => '.htm' 
		),
		'page' => array (
				'page_var' => 'p',
				'page_size' => 30 
		),
		'security' => array (
				'urlxssdefend' => 1,
				'attackevasive' => '0',
				'querysafe' => array (
						'status' => 1,
						'dfunction' => array (
								0 => 'load_file',
								1 => 'hex',
								2 => 'substring',
								3 => 'if',
								4 => 'ord',
								5 => 'char' 
						),
						'daction' => array (
								0 => 'intooutfile',
								1 => 'intodumpfile',
								2 => 'unionselect',
								3 => '(select',
								4 => 'unionall',
								5 => 'uniondistinct' 
						),
						'dnote' => array (
								0 => '/*',
								1 => '*/',
								2 => '#',
								3 => '--',
								4 => '"' 
						),
						'dlikehex' => 1,
						'afullnote' => '0' 
				) 
		),
		'debug' => 1,
		'route'=>array(
				'default_controller' => 'index',
				'default_action' => 'index',
				
		), 
)?>

