<?php
if(!defined('IN_QIT')) {
	exit('Access Denied');
}

/**
 * Qit框架模型类
 *
 * @author Harold
 * @version $Id: Qit.php 2012-05-13 11:16:52Z Harold $
 * @package Qit
 * @since 1.0
 */
class Qit_model extends Qit_base
{

	public $tablename = '';
	public static $db;
	public static $driver;
	private static $_instance = null;
	protected static $checkcmd = array('SELECT', 'UPDATE', 'INSERT', 'REPLACE', 'DELETE');
	protected static $config;
	
	public static function object(){
		return self::$db;		
	}
	
	/**
	 * 初始化模型并连接数据库
	 * @param string $driver 数据库类型（MySQL、PostgreSQL等）
	 * @param array $config 数据库连接配置
	 */
	public function init($driver,$config){
		self::$driver = $driver; 
		self::$db = new $driver;
		self::$db->set_config($config);
		self::$db->connect();
	}
	
	/**
	 * 获取模型表名称（可省略表前缀）
	 * @param string $tablename 表名称（不带前缀）
	 */
	public function table($tablename='') {
		if(!$tablename)
			$tablename = $this->tablename;
		return self::$db->table_name($tablename);
	}
	
	/**
	 * 删除指定数据
	 * @param string $table 表名称
	 * @param array|string $condition 删除数据时的限定条件
	 * @param int $limit 删除行数
	 * @param boolean $unbuffered 是否开启缓存结果
	 * @return boolean
	 */
	public static function delete($table, $condition, $limit = 0, $unbuffered = true) {
		if (empty($condition)) {
			return false;
		} elseif (is_array($condition)) {
			if (count($condition) == 2 && isset($condition['where']) && isset($condition['arg'])) {
				$where = self::format($condition['where'], $condition['arg']);
			} else {
				$where = self::implode_field_value($condition, ' AND ');
			}
		} else {
			$where = $condition;
		}
		$limit = intval($limit);
		$sql = "DELETE FROM " . self::table($table) . " WHERE $where " . ($limit ? "LIMIT $limit" : '');
		return self::query($sql, ($unbuffered ? 'UNBUFFERED' : ''));
	}
	
	/**
	 * 插入数据
	 * @param string $table 表名称
	 * @param mixed $data 待插入的数据
	 * @param boolean $return_insert_id 是否返回插入数据后的ID
	 * @param boolean $replace 替换或直接插入
	 * @param boolean $silent 
	 */
	public static function insert($table, $data, $return_insert_id = false, $replace = false, $silent = false) {
	
		$sql = self::implode($data);
	
		$cmd = $replace ? 'REPLACE INTO' : 'INSERT INTO';
	
		$table = self::table($table);
		$silent = $silent ? 'SILENT' : '';
	
		return self::query("$cmd $table SET $sql", null, $silent, !$return_insert_id);
	}
	
	/**
	 * 更新指定数据
	 * @param string $table 表名称
	 * @param mixed $data 待更新的数据
	 * @param array|string $condition 更新数据时的限定条件
	 * @param boolean $unbuffered 是否开启缓存结果
	 * @param unknown_type $low_priority 是否启用写操作的优先级（让查询优先）
	 * @return boolean|unknown
	 */
	public static function update($table, $data, $condition, $unbuffered = false, $low_priority = false) {
		$sql = self::implode($data);
		if(empty($sql)) {
			return false;
		}
		$cmd = "UPDATE " . ($low_priority ? 'LOW_PRIORITY' : '');
		$table = self::table($table);
		$where = '';
		if (empty($condition)) {
			$where = '1';
		} elseif (is_array($condition)) {
			$where = self::implode($condition, ' AND ');
		} else {
			$where = $condition;
		}
		$res = self::query("$cmd $table SET $sql WHERE $where", $unbuffered ? 'UNBUFFERED' : '');
		return $res;
	}
	
	/**
	 * 返回最后一次插入的ID
	 */
	public static function insert_id() {
		return self::$db->insert_id();
	}
	
	/**
	 * 获取数据行
	 * @param resource $resourceid 查询结果集
	 * @param string $type 获取数据结果方式
	 */
	public static function fetch($resourceid, $type = MYSQL_ASSOC) {
		return self::$db->fetch_array($resourceid, $type);
	}
	
	/**
	 * 获取查询数据行中第一条数据
	 * @param string $sql 待查询的语句
	 * @param array $arg 查询参数
	 * @param boolean $silent
	 * @return 
	 */
	public static function fetch_first($sql, $arg = array(), $silent = false) {
		$res = self::query($sql, $arg, $silent, false);
		$ret = self::$db->fetch_array($res);
		self::$db->free_result($res);
		return $ret ? $ret : array();
	}
	
	/**
	 * 获取所有查询结果
	 * @param string $sql 待查询的语句
	 * @param array $arg 查询参数
	 * @param unknown_type $keyfield
	 * @param unknown_type $silent
	 * @return 
	 */
	public static function fetch_all($sql, $arg = array(), $keyfield = '', $silent=false) {
	
		$data = array();
		$query = self::query($sql, $arg, $silent, false);
		while ($row = self::$db->fetch_array($query)) {
			if ($keyfield && isset($row[$keyfield])) {
				$data[$row[$keyfield]] = $row;
			} else {
				$data[] = $row;
			}
		}
		self::$db->free_result($query);
		return $data;
	}
	
	public static function result($resourceid, $row = 0) {
		return self::$db->result($resourceid, $row);
	}
	
	public static function result_first($sql, $arg = array(), $silent = false) {
		$res = self::query($sql, $arg, $silent, false);
		$ret = self::$db->result($res, 0);
		self::$db->free_result($res);
		return $ret;
	}
	
	public static function query($sql, $arg = array(), $silent = false, $unbuffered = false) {
		if (!empty($arg)) {
			if (is_array($arg)) {
				$sql = self::format($sql, $arg);
			} elseif ($arg === 'SILENT') {
				$silent = true;
	
			} elseif ($arg === 'UNBUFFERED') {
				$unbuffered = true;
			}
		}
		self::checkquery($sql);
	
		$ret = self::$db->query($sql, $silent, $unbuffered);
		if (!$unbuffered && $ret) {
			$cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
			if ($cmd === 'SELECT') {
	
			} elseif ($cmd === 'UPDATE' || $cmd === 'DELETE') {
				$ret = self::$db->affected_rows();
			} elseif ($cmd === 'INSERT') {
				$ret = self::$db->insert_id();
			}
		}
		return $ret;
	}
	
	public static function num_rows($resourceid) {
		return self::$db->num_rows($resourceid);
	}
	
	public static function affected_rows() {
		return self::$db->affected_rows();
	}
	
	public static function free_result($query) {
		return self::$db->free_result($query);
	}
	
	public static function error() {
		return self::$db->error();
	}
	
	public static function errno() {
		return self::$db->errno();
	} 
	
	public static function checkquery($sql) {
		if (self::$config === null) {
			self::$config = Qit::app()->config('security/querysafe');
		}
		if (self::$config['status']) {
			$cmd = trim(strtoupper(substr($sql, 0, strpos($sql, ' '))));
			if (in_array($cmd, self::$checkcmd)) {
				$test = self::_do_query_safe($sql);
				if ($test < 1) {
					throw new Qit_DbException('It is not safe to do this query', 0, $sql);
				}
			}
		}
		return true;
	}
	
	/**
	 * 进行数据库查询操作时，判断查询语句中是否有非法查询
	 * @param unknown_type $sql
	 * @return string|number
	 */
	protected static function _do_query_safe($sql) {
		$sql = str_replace(array('\\\\', '\\\'', '\\"', '\'\''), '', $sql);
		$mark = $clean = '';
		if (strpos($sql, '/') === false && strpos($sql, '#') === false && strpos($sql, '-- ') === false) {
			$clean = preg_replace("/'(.+?)'/s", '', $sql);
		} else {
			$len = strlen($sql);
			$mark = $clean = '';
			for ($i = 0; $i < $len; $i++) {
				$str = $sql[$i];
				switch ($str) {
					case '\'':
						if (!$mark) {
							$mark = '\'';
							$clean .= $str;
						} elseif ($mark == '\'') {
							$mark = '';
						}
						break;
					case '/':
						if (empty($mark) && $sql[$i + 1] == '*') {
							$mark = '/*';
							$clean .= $mark;
							$i++;
						} elseif ($mark == '/*' && $sql[$i - 1] == '*') {
							$mark = '';
							$clean .= '*';
						}
						break;
					case '#':
						if (empty($mark)) {
							$mark = $str;
							$clean .= $str;
						}
						break;
					case "\n":
						if ($mark == '#' || $mark == '--') {
							$mark = '';
						}
						break;
					case '-':
						if (empty($mark) && substr($sql, $i, 3) == '-- ') {
							$mark = '-- ';
							$clean .= $mark;
						}
						break;
	
					default:
	
						break;
				}
				$clean .= $mark ? '' : $str;
			}
		}
	
		$clean = preg_replace("/[^a-z0-9_\-\(\)#\*\/\"]+/is", "", strtolower($clean));
	
		if (self::$config['afullnote']) {
			$clean = str_replace('/**/', '', $clean);
		}
	
		if (is_array(self::$config['dfunction'])) {
			foreach (self::$config['dfunction'] as $fun) {
				if (strpos($clean, $fun . '(') !== false)
					return '-1';
			}
		}
	
		if (is_array(self::$config['daction'])) {
			foreach (self::$config['daction'] as $action) {
				if (strpos($clean, $action) !== false)
					return '-3';
			}
		}
	
		if (self::$config['dlikehex'] && strpos($clean, 'like0x')) {
			return '-2';
		}
	
		if (is_array(self::$config['dnote'])) {
			foreach (self::$config['dnote'] as $note) {
				if (strpos($clean, $note) !== false)
					return '-4';
			}
		}
	
		return 1;
	}
	
	public static function setconfigstatus($data) {
		self::$config['status'] = $data ? 1 : 0;
	}
	
	public static function quote($str, $noarray = false) {
	
		if (is_string($str))
			return '\'' . addcslashes($str, "\n\r\\'\"\032") . '\'';
	
		if (is_int($str) or is_float($str))
			return $str;
	
		if (is_array($str)) {
			if($noarray === false) {
				foreach ($str as &$v) {
					$v = self::quote($v, true);
				}
				return $str;
			} else {
				return '\'\'';
			}
		}
	
		if (is_bool($str))
			return $str ? '1' : '0';
	
		return '\'\'';
	}
	
	public static function quote_field($field) {
		if (is_array($field)) {
			foreach ($field as $k => $v) {
				$field[$k] = self::quote_field($v);
			}
		} else {
			if (strpos($field, '`') !== false)
				$field = str_replace('`', '', $field);
			$field = '`' . $field . '`';
		}
		return $field;
	}
	
	public static function limit($start, $limit = 0) {
		$limit = intval($limit > 0 ? $limit : 0);
		$start = intval($start > 0 ? $start : 0);
		if ($start && $limit) {
			return " LIMIT $start, $limit";
		} elseif ($limit) {
		return " LIMIT $limit";
		} elseif ($start) {
		return " LIMIT $start";
		} else {
		return '';
		}
		}
	
		public static function order($field, $order = 'ASC') {
		if(empty($field)) {
		return '';
		}
		$order = strtoupper($order) == 'ASC' || empty($order) ? 'ASC' : 'DESC';
		return self::quote_field($field) . ' ' . $order;
		}
	
		public static function field($field, $val, $glue = '=') {
	
		$field = self::quote_field($field);
	
		if (is_array($val)) {
		$glue = $glue == 'notin' ? 'notin' : 'in';
		} elseif ($glue == 'in') {
		$glue = '=';
		}
	
			switch ($glue) {
			case '=':
			return $field . $glue . self::quote($val);
			break;
			case '-':
			case '+':
			return $field . '=' . $field . $glue . self::quote((string) $val);
			break;
			case '|':
			case '&':
			case '^':
			return $field . '=' . $field . $glue . self::quote($val);
			break;
			case '>':
			case '<':
			case '<>':
			case '<=':
			case '>=':
			return $field . $glue . self::quote($val);
			break;
	
			case 'like':
			return $field . ' LIKE(' . self::quote($val) . ')';
			break;
	
			case 'in':
			case 'notin':
				$val = $val ? implode(',', self::quote($val)) : '\'\'';
				return $field . ($glue == 'notin' ? ' NOT' : '') . ' IN(' . $val . ')';
				break;
	
						default:
							throw new Qit_DbException('Not allow this glue between field and value: "' . $glue . '"');
		}
	}
	
	public static function implode($array, $glue = ',') {
		$sql = $comma = '';
		$glue = ' ' . trim ( $glue ) . ' ';
		foreach ( $array as $k => $v ) {
			$sql .= $comma . self::quote_field ( $k ) . '=' . self::quote ( $v );
			$comma = $glue;
		}
		return $sql;
	}
	public static function implode_field_value($array, $glue = ',') {
		return self::implode ( $array, $glue );
	}
	public static function format($sql, $arg) {
		$count = substr_count ( $sql, '%' );
		if (! $count) {
			return $sql;
		} elseif ($count > count ( $arg )) {
			throw new Qit_DbException ( 'SQL string format error! This SQL need "' . $count . '" vars to replace into.', 0, $sql );
		}
	
		$len = strlen($sql);
		$i = $find = 0;
		$ret = '';
		while ($i <= $len && $find < $count) {
		if ($sql{$i} == '%') {
			$next = $sql{$i + 1};
			if ($next == 't') {
				$ret .= self::table($arg[$find]);
			} elseif ($next == 's') {
				$ret .= self::quote(is_array($arg[$find]) ? serialize($arg[$find]) : (string) $arg[$find]);
			} elseif ($next == 'f') {
				$ret .= sprintf('%F', $arg[$find]);
			} elseif ($next == 'd') {
				$ret .= dintval($arg[$find]);
			} elseif ($next == 'i') {
				$ret .= $arg[$find];
			} elseif ($next == 'n') {
				if (!empty($arg[$find])) {
					$ret .= is_array($arg[$find]) ? implode(',', self::quote($arg[$find])) : self::quote($arg[$find]);
				} else {
					$ret .= '0';
				}
			} else {
				$ret .= self::quote($arg[$find]);
			}
			$i++;
			$find++;
		} else {
			$ret .= $sql{$i};
		}
		$i++;
		}
		if ($i < $len) {
			$ret .= substr($sql, $i);
		}
		return $ret;
		}
	
	}
	 