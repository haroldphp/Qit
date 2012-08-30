<?php
if(!defined('IN_QIT')) {
	exit('Access Denied');
}

/**
 * Qit客户端应用类
 *
 * @author Harold
 * @version $Id: client.php 2012-07-25 16:33:07Z Harold $
 * @package libraries
 * @since 1.0
 */
class Qit_client
{
	/**
	 * 获取客户端IP地址
	 * @return string
	 */
	public static function getIp(){
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
	
	/**
	 * 获取客户端ip物理地址
	 * @param string $ip ip地址
	 * @param string $type 需要获取的ip地址数据库类型
	 * @return string
	 */
	public static function get_ip_address($ip,$type='full'){
		$return = '';
		if(preg_match("/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/", $ip)) {
		
			$iparray = explode('.', $ip);
		
			if($iparray[0] == 10 || $iparray[0] == 127 || ($iparray[0] == 192 && $iparray[1] == 168) || ($iparray[0] == 172 && ($iparray[1] >= 16 && $iparray[1] <= 31))) {
				$return = '- LAN';
			} elseif($iparray[0] > 255 || $iparray[1] > 255 || $iparray[2] > 255 || $iparray[3] > 255) {
				$return = '- Invalid IP Address';
			} else {
				$tinyipfile = APP_PROTECTED_PATH . '/data/ipdata/tinyipdata.dat';
				$fullipfile = APP_PROTECTED_PATH.'/data/ipdata/wry.dat';
				if($type=='full' && @file_exists($fullipfile)) {
					$ipaddress = self::convertip_full($ip, $fullipfile);
					$ipaddress = iconv('gbk', 'utf-8', $ipaddress);
					$tmp = explode(' ',$ipaddress);
					$return = $tmp[0];
				}elseif(@file_exists($tinyipfile)) {
					$return = self::convertip_tiny($ip, $tinyipfile);
				}else{
					$return = 'unknow';
				}
			}
		}
		return $return;
	}
	
	/**
	 * 转换ip地址为物理地址（tiny数据库）
	 * @param string $ip ip地址
	 * @param string $ipdatafile ip数据库文件地址
	 * @return string
	 */
	public static function convertip_tiny($ip, $ipdatafile) {
	
		static $fp = NULL, $offset = array(), $index = NULL;
	
		$ipdot = explode('.', $ip);
		$ip    = pack('N', ip2long($ip));
	
		$ipdot[0] = (int)$ipdot[0];
		$ipdot[1] = (int)$ipdot[1];
	
		if($fp === NULL && $fp = @fopen($ipdatafile, 'rb')) {
			$offset = @unpack('Nlen', @fread($fp, 4));
			$index  = @fread($fp, $offset['len'] - 4);
		} elseif($fp == FALSE) {
			return  '- Invalid IP data file';
		}
	
		$length = $offset['len'] - 1028;
		$start  = @unpack('Vlen', $index[$ipdot[0] * 4] . $index[$ipdot[0] * 4 + 1] . $index[$ipdot[0] * 4 + 2] . $index[$ipdot[0] * 4 + 3]);
	
		for ($start = $start['len'] * 8 + 1024; $start < $length; $start += 8) {
	
			if ($index{$start} . $index{$start + 1} . $index{$start + 2} . $index{$start + 3} >= $ip) {
				$index_offset = @unpack('Vlen', $index{$start + 4} . $index{$start + 5} . $index{$start + 6} . "\x0");
				$index_length = @unpack('Clen', $index{$start + 7});
				break;
			}
		}
	
		@fseek($fp, $offset['len'] + $index_offset['len'] - 1024);
		if($index_length['len']) {
			return @fread($fp, $index_length['len']);
		} else {
			return '- Unknown';
		}
	
	}
	
	/**
	 * 转换ip地址为物理地址（full数据库，更精确，但可能执行时间稍长）
	 * @param string $ip ip地址
	 * @param string $ipdatafile ip数据库文件地址
	 * @return string
	 */
	public static function convertip_full($ip, $ipdatafile) {
	
		if(!$fd = @fopen($ipdatafile, 'rb')) {
			return '- Invalid IP data file';
		}
	
		$ip = explode('.', $ip);
		$ipNum = $ip[0] * 16777216 + $ip[1] * 65536 + $ip[2] * 256 + $ip[3];
	
		if(!($DataBegin = fread($fd, 4)) || !($DataEnd = fread($fd, 4)) ) return;
		@$ipbegin = implode('', unpack('L', $DataBegin));
		if($ipbegin < 0) $ipbegin += pow(2, 32);
		@$ipend = implode('', unpack('L', $DataEnd));
		if($ipend < 0) $ipend += pow(2, 32);
		$ipAllNum = ($ipend - $ipbegin) / 7 + 1;
	
		$BeginNum = $ip2num = $ip1num = 0;
		$ipAddr1 = $ipAddr2 = '';
		$EndNum = $ipAllNum;
	
		while($ip1num > $ipNum || $ip2num < $ipNum) {
			$Middle= intval(($EndNum + $BeginNum) / 2);
	
			fseek($fd, $ipbegin + 7 * $Middle);
			$ipData1 = fread($fd, 4);
			if(strlen($ipData1) < 4) {
				fclose($fd);
				return '- System Error';
			}
			$ip1num = implode('', unpack('L', $ipData1));
			if($ip1num < 0) $ip1num += pow(2, 32);
	
			if($ip1num > $ipNum) {
				$EndNum = $Middle;
				continue;
			}
	
			$DataSeek = fread($fd, 3);
			if(strlen($DataSeek) < 3) {
				fclose($fd);
				return '- System Error';
			}
			$DataSeek = implode('', unpack('L', $DataSeek.chr(0)));
			fseek($fd, $DataSeek);
			$ipData2 = fread($fd, 4);
			if(strlen($ipData2) < 4) {
				fclose($fd);
				return '- System Error';
			}
			$ip2num = implode('', unpack('L', $ipData2));
			if($ip2num < 0) $ip2num += pow(2, 32);
	
			if($ip2num < $ipNum) {
				if($Middle == $BeginNum) {
					fclose($fd);
					return '- Unknown';
				}
				$BeginNum = $Middle;
			}
		}
	
		$ipFlag = fread($fd, 1);
		if($ipFlag == chr(1)) {
			$ipSeek = fread($fd, 3);
			if(strlen($ipSeek) < 3) {
				fclose($fd);
				return '- System Error';
			}
			$ipSeek = implode('', unpack('L', $ipSeek.chr(0)));
			fseek($fd, $ipSeek);
			$ipFlag = fread($fd, 1);
		}
	
		if($ipFlag == chr(2)) {
			$AddrSeek = fread($fd, 3);
			if(strlen($AddrSeek) < 3) {
				fclose($fd);
				return '- System Error';
			}
			$ipFlag = fread($fd, 1);
			if($ipFlag == chr(2)) {
				$AddrSeek2 = fread($fd, 3);
				if(strlen($AddrSeek2) < 3) {
					fclose($fd);
					return '- System Error';
				}
				$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
				fseek($fd, $AddrSeek2);
			} else {
				fseek($fd, -1, SEEK_CUR);
			}
	
			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr2 .= $char;
	
			$AddrSeek = implode('', unpack('L', $AddrSeek.chr(0)));
			fseek($fd, $AddrSeek);
	
			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr1 .= $char;
		} else {
			fseek($fd, -1, SEEK_CUR);
			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr1 .= $char;
	
			$ipFlag = fread($fd, 1);
			if($ipFlag == chr(2)) {
				$AddrSeek2 = fread($fd, 3);
				if(strlen($AddrSeek2) < 3) {
					fclose($fd);
					return '- System Error';
				}
				$AddrSeek2 = implode('', unpack('L', $AddrSeek2.chr(0)));
				fseek($fd, $AddrSeek2);
			} else {
				fseek($fd, -1, SEEK_CUR);
			}
			while(($char = fread($fd, 1)) != chr(0))
				$ipAddr2 .= $char;
		}
		fclose($fd);
	
		if(preg_match('/http/i', $ipAddr2)) {
			$ipAddr2 = '';
		}
		$ipaddr = "$ipAddr1 $ipAddr2";
		$ipaddr = preg_replace('/CZ88\.NET/is', '', $ipaddr);
		$ipaddr = preg_replace('/^\s*/is', '', $ipaddr);
		$ipaddr = preg_replace('/\s*$/is', '', $ipaddr);
		if(preg_match('/http/i', $ipaddr) || $ipaddr == '') {
			$ipaddr = '- Unknown';
		}
	
		return $ipaddr;
	
	}
}