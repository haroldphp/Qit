<?php
if(!defined('IN_QIT')) {
	exit('Access Denied');
}
class Qit_error
{
	public static function system_error($message, $show = false, $save = true, $halt = true) {
		if(!empty($message)) {
			$message = Qit::t('error', $message);
		} else {
			$message = Qit::t('error', 'error_unknow');
		}

		list($showtrace, $logtrace) = self::debug_backtrace();

		if($save) {
			$messagesave = '<b>'.$message.'</b><br><b>PHP:</b>'.$logtrace;
			self::write_error_log($messagesave);
		}

		if($show) {
			if(!defined('IN_MOBILE')) {
				self::show_error('system', "<li>$message</li>", $showtrace, 0);
			} else {
				self::mobile_show_error('system', "<li>$message</li>", $showtrace, 0);
			}
		} 

		if($halt) {
			exit();
		} else {
			return $message;
		}
	}
	
	public static function debug_backtrace() {
		$skipfunc[] = 'Qit_error->debug_backtrace';
		$skipfunc[] = 'Qit_error->db_error';
		$skipfunc[] = 'Qit_error->template_error';
		$skipfunc[] = 'Qit_error->system_error';
		$skipfunc[] = 'db_mysql->halt';
		$skipfunc[] = 'db_mysql->query';
		$skipfunc[] = 'DB::_execute';
	
		$show = $log = '';
		$debug_backtrace = debug_backtrace();
		krsort($debug_backtrace);
		foreach ($debug_backtrace as $k => $error) {
			$file = str_replace(Qit_ROOT, '', $error['file']);
			$func = isset($error['class']) ? $error['class'] : '';
			$func .= isset($error['type']) ? $error['type'] : '';
			$func .= isset($error['function']) ? $error['function'] : '';
			if(in_array($func, $skipfunc)) {
				break;
			}
			$error[line] = sprintf('%04d', $error['line']);
	
			$show .= "<li>[Line: $error[line]]".$file."($func)</li>";
			$log .= !empty($log) ? ' -> ' : '';$file.':'.$error['line'];
			$log .= $file.':'.$error['line'];
		}
		return array($show, $log);
	}
	
	public static function sql_clear($message) {
		$message = self::clear($message);
		$message = str_replace(Qit::app()->db->tablepre, '', $message);
		$message = htmlspecialchars($message);
		return $message;
	}
	
	public static function exception_error($exception) {

		if($exception instanceof Qit_DbException) { 
			$type = 'db';
		} else {
			$type = 'system';
		}

		if($type == 'db') {
			$errormsg = '('.$exception->getCode().') ';
			$errormsg .= self::sql_clear($exception->getMessage());
			if($exception->getSql()) {
				$errormsg .= '<div class="sql">';
				$errormsg .= self::sql_clear($exception->getSql());
				$errormsg .= '</div>';
			}
		} else {
			$errormsg = $exception->getMessage();
		}

		$trace = $exception->getTrace();
		krsort($trace);

		$trace[] = array('file'=>$exception->getFile(), 'line'=>$exception->getLine(), 'function'=> 'break');
		$phpmsg = array();
		foreach ($trace as $error) {
			if(!empty($error['function'])) {
				$fun = '';
				if(!empty($error['class'])) {
					$fun .= $error['class'].$error['type'];
				}
				$fun .= $error['function'].'(';
				if(!empty($error['args'])) {
					$mark = '';
					foreach($error['args'] as $arg) {
						$fun .= $mark;
						if(is_array($arg)) {
							$fun .= 'Array';
						} elseif(is_bool($arg)) {
							$fun .= $arg ? 'true' : 'false';
						} elseif(is_int($arg)) {
							$fun .= (defined('SYSTEM_CORE_DEBUG') && SYSTEM_CORE_DEBUG) ? $arg : '%d';
						} elseif(is_float($arg)) {
							$fun .= (defined('SYSTEM_CORE_DEBUG') && SYSTEM_CORE_DEBUG) ? $arg : '%f';
						} else {
							$fun .= (defined('SYSTEM_CORE_DEBUG') && SYSTEM_CORE_DEBUG) ? '\''.htmlspecialchars(substr(self::clear($arg), 0, 20)).(strlen($arg) > 20 ? ' ...' : '').'\'' : '%s';
						}
						$mark = ', ';
					}
				}

				$fun .= ')';
				$error['function'] = $fun;
			}
			$phpmsg[] = array(
			    'file' => str_replace(array(QIT_ROOT, '\\'), array('', '/'), $error['file']),
			    'line' => $error['line'],
			    'function' => $error['function'],
			);
		}

		self::show_error($type, $errormsg, $phpmsg);
		exit();

	}
	
	public static function show_error($type, $errormsg, $phpmsg = '', $typemsg = '', $exit=true) {		
		#ob_end_clean();
		$host = $_SERVER['HTTP_HOST'];
		$title = $type == 'db' ? 'Database' : 'System';
		echo <<<EOT
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<title>$host - $title Error</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="ROBOTS" content="NOINDEX,NOFOLLOW,NOARCHIVE" />
	<style type="text/css">
	<!--
	body { background-color: white; color: black; font: 9pt/11pt verdana, arial, sans-serif;}
	#container { width: 1024px; }
	#message   { width: 1024px; color: black; }

	.red  {color: red;}
	a:link     { font: 9pt/11pt verdana, arial, sans-serif; color: red; }
	a:visited  { font: 9pt/11pt verdana, arial, sans-serif; color: #4e4e4e; }
	h1 { color: #FF0000; font: 18pt "Verdana"; margin-bottom: 0.5em;}
	.bg1{ background-color: #FFFFCC;}
	.bg2{ background-color: #EEEEEE;}
	.table {background: #AAAAAA; font: 11pt Menlo,Consolas,"Lucida Console"}
	.info {
	    background: none repeat scroll 0 0 #F3F3F3;
	    border: 0px solid #aaaaaa;
	    border-radius: 10px 10px 10px 10px;
	    color: #000000;
	    font-size: 11pt;
	    line-height: 160%;
	    margin-bottom: 1em;
	    padding: 1em;
	}

	.help {
	    background: #F3F3F3;
	    border-radius: 10px 10px 10px 10px;
	    font: 12px verdana, arial, sans-serif;
	    text-align: center;
	    line-height: 160%;
	    padding: 1em;
	}

	.sql {
	    background: none repeat scroll 0 0 #FFFFCC;
	    border: 1px solid #aaaaaa;
	    color: #000000;
	    font: arial, sans-serif;
	    font-size: 9pt;
	    line-height: 160%;
	    margin-top: 1em;
	    padding: 4px;
	}
	-->
	</style>
</head>
<body>
<div id="container">
<h1>Qit: $title Error</h1>
<div class='info'>$errormsg</div>


EOT;
		if(is_array($phpmsg) && !empty($phpmsg)) {
			echo '<div class="info">';
			echo '<p><strong>PHP Debug</strong></p>';
			echo '<table cellpadding="5" cellspacing="1" width="100%" class="table">';
			echo '<tr class="bg2"><td>No.</td><td>File</td><td>Line</td><td>Code</td></tr>';
			foreach($phpmsg as $k => $msg) {
				$k++;
				echo '<tr class="bg1">';
				echo '<td>'.$k.'</td>';
				echo '<td>'.$msg['file'].'</td>';
				echo '<td>'.$msg['line'].'</td>';
				echo '<td>'.$msg['function'].'</td>';
				echo '</tr>';
			}
			echo '</table></div>';
		}


		$helplink = '';

		$endmsg = Qit::t('error', 'error_end_message', array('host'=>$host)); 
		echo <<<EOT
<div class="help">$endmsg. $helplink</div>
</div>
</body>
</html>
EOT;
		$exit && exit();

	}
	
	public static function clear($message) {
		return str_replace(array("\t", "\r", "\n"), " ", $message);
	}
	
	public static function template_error($message, $tplname) {
		$message = Qit::t('error', $message);
		$tplname = str_replace(APP_ROOT, '', $tplname);
		$message = $message.': '.$tplname;
		self::system_error($message);
	}
	
	/**
	 * 获取客户端访问时的IP
	 * @return string $ip
	 */
	public static function get_client_ip() {
		$ip = $_SERVER['REMOTE_ADDR'];
		if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR']) AND preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
			foreach ($matches[0] AS $xip) {
				if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
					$ip = $xip;
					break;
				}
			}
		}
		return $ip;
	}
	
	public static function write_error_log($message) {
	
		$message = self::clear($message);
		$time = time();
		$file =  Qit_APP_PATH.'/protected/data/errorlog/'.date("Y-m-d").'_errorlog.php';
		$hash = md5($message);
	
		$ip = self::get_client_ip();
	
		$user = '<b>User:</b> IP='.$ip.'; RIP:'.$_SERVER['REMOTE_ADDR'];
		$uri = 'Request: '.htmlspecialchars(self::clear($_SERVER['REQUEST_URI']));
		$message = "<?PHP exit;?>\t{$time}\t$message\t$hash\t$user $uri\n"; 
		if($fp = @fopen($file, 'rb')) {
			$lastlen = 10000;
			$maxtime = 60 * 10;
			$offset = filesize($file) - $lastlen;
			if($offset > 0) {
				fseek($fp, $offset);
			}
			if($data = fread($fp, $lastlen)) {
				$array = explode("\n", $data);
				if(is_array($array)) foreach($array as $key => $val) {
					$row = explode("\t", $val);
					if($row[0] != '<?PHP exit;?>') continue;
					if($row[3] == $hash && ($row[1] > $time - $maxtime)) {
						return;
					}
				}
			}
		}
		error_log($message, 3, $file);
	}
}