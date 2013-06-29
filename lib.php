<?php
if (!defined('DWTOOLS')) define('DWTOOLS',DOKU_INC.'lib/plugins/dwtools/');
require_once (DWTOOLS.'db.php');
/**
 * functions that are used by dw-plugins and dw-templates
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Dietrich Wittenberg <info.wittenberg@online.de>
 */


	function getRealIpAddr()
	{
		if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
		{
			$ip=$_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
		{
			$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
		}
		else
		{
			$ip=$_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	function array_table($table) {
		$first=true;
		$doc	=	"<table>";
		foreach ($table as $j => $row) {
			if ($first) {
				$first=!first;
				$doc .=	"<tr  style='border: solid 1px;'>";
				foreach ($row as $key => $cell) {
					$doc .=	"<th>";
					$doc .=	$key;
					$doc .=	"</th>";
				}
				$doc .=	"</tr>";
			}
			$doc .=	"<tr>";
			foreach ($row as $key => $cell) {
				$doc .=	"<td style='border: solid 1px; margin: 0px;'>";
				$doc .=	$cell;	
				$doc .=	"</td>";
			}
			$doc .=	"</tr>";
		}
		$doc .=	"<table>";
		return $doc;
	}

/**
 * create a dokuwikitable from array $table
 * $table = array($row)
 * $row   = array($val)  (variante 1 - no formatting, only plain table)
 *        = array($cell) (variante 2 - formatting used)
 *        
 * if variante 2 is used:
 * $cell  = array('val' => cell_value, 'format' => cell_format)
 * if 'format' is ommitted defaults are taken
 * format-element must be seperatet with e.g. space
 * format-values: left       = left-sided (default)
 *                right      = right-sided
 *                left right = centered
 *                TH         = headline
 *                TD         = no headline
 *                link       = 'val' is used as a wikilink
 * @param array(array()) $table
 * @return string with wikitable-syntax
 */
	 function _toWikiTable($table) {
		
		$wikitable="";
		foreach ($table as $i => $row) {
			foreach ($row as $cell) {
				// cell[val], cell[format]; 
				// format: left, right | TH | link
				$pre = "";
				$pst = "";
				$tbl = "|"; 
				if (is_array($cell)) {
					$pre = (strpos($cell['format'], 'right')  !== false) ? "  " : $pre;
					$pst = (strpos($cell['format'], 'left')		!== false) ? "  " : $pst;
	
					$tbl = (strpos($cell['format'], 'TH')			!== false) ? "^" : $tbl;
					$tbl = (strpos($cell['format'], 'TD')			!== false) ? "|" : $tbl;
					
					$pre = (strpos($cell['format'], 'link')		!== false) ? $pre . "[[" : $pre; 
					$pst = (strpos($cell['format'], 'link')		!== false) ? "|" . $cell['val'] . "]]" . $pst : $pst; 
					
					$val = $cell['val'];
				} else {
					$val = $cell;
				}
				$wikitable .= $tbl . $pre . $val .  $pst;
			}
			$wikitable .= $tbl . DOKU_LF;
		}
		return $wikitable;
	}

/**
 * @param array(array($cell)) $table
 * @return array(array($cell)) transponierte tabelle
 */
	function _transposeTable($table) {
		foreach ($table as $r => $row) {
			foreach ($row as $c => $cell) {
				if ($cell) $elbat[$c][$r]=$cell;
			}
		}
		return $elbat;	
	}

	function _tocode($mixed) {	return ("<code>" .var_export($mixed, true). "</code>"); }

/**
 * renders the $result depending on the $format definition
 * format = transpose: transpose $result und check for other format
 * format = headline: define a TH for cells of first row else define as TD
 * format = table: translate the $result to a table with wikitext-format
 * format = code : return the var_dump value of $result
 * format = cell : return the $result[0][0] as it is
 * @param array(array($cell)) $result
 * @param string $format
 * @return boolean / string <string, NULL>
 */
	function _render_result($result, $format="table") {
		if ($result === false) return false;
		
		if (is_array($result)) {
			$i=0;
			foreach($result as $row) {
				// cheack if headline
				if ($i==0 && strpos($format, 'headline')!==false) {
					foreach ($row as $key => $val) 
						$cells[$key] = array('format' => "TH", 'val' => $key);
					$table[$i++] = array_values($cells);
				}
				foreach ($row as $key => $val) {
					$val = ($val=="" || $val==NULL) ? " " : $val;
					//$val = ($key=="tst") ? strftime("%Y-%m-%d %H:%M:%S", $val) : $val;
					$cells[$key] = array('format' => "TD", 'val' => $val);
				}
				$table[$i++] = array_values($cells);	
			}
			// check if to transpose
			if (strpos($format, 'transpose') !== false) $table = _transposeTable($table);
			
			// render the output
			if (strpos($format, 'table') !== false)	$wikitext = p_render("xhtml", p_get_instructions(_toWikiTable($table))	, $info);
			if (strpos($format, 'code' ) !== false)	$wikitext = p_render("xhtml", p_get_instructions(_tocode($table))			, $info);	// ToDo
			if (strpos($format, 'cell' ) !== false)	$wikitext = $table[0][0]['val'];
	
		} else {
																							$wikitext = p_render("xhtml", p_get_instructions(_tocode($result))			, $info);
		}
	
		return $wikitext;
	}

	function _isinlist($search, $blackstring) {
		$blacklist = explode(",",str_replace(" ", "", $blackstring));	// remove spaces and split by ','
		$isblack=false;
		foreach ($blacklist as $black) {
			$p=strpos($search, $black);
			if ($p !== false && $p == 0) {$isblack=true; break; }
		}
		return $isblack;
	}

/* ---------------------------------- */
/* code from: http://zenverse.net/php-function-to-auto-convert-url-into-hyperlink/
 * 
 */
	function _make_url_clickable_cb($matches) {
		$ret = '';
		$url = $matches[2];
	 
		if ( empty($url) )
			return $matches[0];
		// removed trailing [.,;:] from URL
		if ( in_array(substr($url, -1), array('.', ',', ';', ':')) === true ) {
			$ret = substr($url, -1);
			$url = substr($url, 0, strlen($url)-1);
		}
		return $matches[1] . "<a href=\"$url\" rel=\"nofollow\">$url</a>" . $ret;
	}
 
	function _make_web_ftp_clickable_cb($matches) {
		$ret = '';
		$dest = $matches[2];
		$dest = 'http://' . $dest;
	 
		if ( empty($dest) )
			return $matches[0];
		// removed trailing [,;:] from URL
		if ( in_array(substr($dest, -1), array('.', ',', ';', ':')) === true ) {
			$ret = substr($dest, -1);
			$dest = substr($dest, 0, strlen($dest)-1);
		}
		return $matches[1] . "<a href=\"$dest\" rel=\"nofollow\">$dest</a>" . $ret;
	}
 
	function _make_email_clickable_cb($matches) {
		$email = $matches[2] . '@' . $matches[3];
		return $matches[1] . "<a href=\"mailto:$email\">$email</a>";
	}
 
	function make_clickable($ret) {
		$ret = ' ' . $ret;
		// in testing, using arrays here was found to be faster
		$ret = preg_replace_callback('#([\s>])([\w]+?://[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is'	,	'_make_url_clickable_cb'		, $ret);
		$ret = preg_replace_callback('#([\s>])((www|ftp)\.[\w\\x80-\\xff\#$%&~/.\-;:=,?@\[\]+]*)#is', '_make_web_ftp_clickable_cb', $ret);
		$ret = preg_replace_callback('#([\s>])([.0-9a-z_+-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,})#i'			,	'_make_email_clickable_cb'	, $ret);
	 
		// this one is not in an array because we need it to run last, for cleanup of accidental links within links
		$ret = preg_replace("#(<a( [^>]+?>|>))<a [^>]+?>([^>]+?)</a></a>#i", "$1$3</a>", $ret);
		$ret = trim($ret);
		return $ret;
	}
/* ---------------------------------- */

	/**
	 * find first wikilink inside a string and return the link and type of link
	 *
	 * @param string $section
	 * @return multitype:unknown |boolean
	 */
	function _get_firstlink($wikitext) {
		// init
		$ltrs = '\w';
		$gunk = '/\#~:.?+=&%@!\-\[\]';
		$punc = '.:?\-;,';
		$host = $ltrs.$punc;
		$any  = $ltrs.$gunk.$punc;
		// wikilink
		$patterns['internallink'][] 		= '#\[\[(?:(?:[^[\]]*?\[.*?\])|.*?)\]\]#s';
		// externallink
		$schemes = getSchemes();
		foreach ( $schemes as $scheme ) {
			$patterns['externallink'][] 	= '#\b(?i)'.$scheme.'(?-i)://['										 .$any.']+?(?=['.$punc.']*[^'.$any.'])#s';
		}
		$patterns['externallink'][]			= '#\b(?i)www?(?-i)\.['.$host.']+?\.['.$host.']+?['.$any.']+?(?=['.$punc.']*[^'.$any.'])#s';
		$patterns['externallink'][] 		= '#\b(?i)ftp?(?-i)\.['.$host.']+?\.['.$host.']+?['.$any.']+?(?=['.$punc.']*[^'.$any.'])#s';
		// filelink
		$patterns['filelink'][] 				= '#\b(?i)file(?-i)://['													 .$any.']+?['	  .$punc.']*[^'.$any.']#s';
		// windows sharelink
		$patterns['windowssharelink'][] = '#\\\\\\\\\w+?(?:\\\\[\w$]+)+#s';
		// wiki medialink
		//$patterns['internalmedia'][] 		= '#\{\{[^\}]+\}\}#s';
		$patterns['media'][] 						= '#\{\{[^\}]+\}\}#s';
		//$this->patterns[] = '#(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?#s?'; // externallink
		// E-Mail
		$patterns['emaillink'][] 				= '#([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})#s';
	
		foreach ($patterns as $type => $subpatterns) {
			foreach ($subpatterns as $pattern) {
				if (preg_match($pattern, $wikitext, $result)) {
					return array($type, $result[0]);
				}
			}
		}
		return false;
	}
	
	/**
	 * Strips the heading <p> and trailing </p> added by p_render xhtml to acheive inline behavior
	 *
	 * @param string $data
	 * @return strin $data
	 */
	function _stripp($data) {
		$data = preg_replace('`^\s*<p[^>]*>\s*`', '', $data);
		$data = preg_replace('`\s*</p[^>]*>\s*$`', '', $data);
		return $data;
	}
	
	
	function mkptln($string,$indent=0){
		return str_repeat(' ', $indent)."$string\n";
	}

	function mylog($txt, $mode="w") {
		$myFile = "./log.txt";
		$fh = fopen($myFile, $mode) or die("can't open file");
		fwrite($fh, $txt."\n");
		fclose($fh);
	}
	
	// Dokuwiki-stuff
	function _get_handler_calls_by_type(&$handler, $type, $link, $state, $pos) {
		//----- save calls-array
		$save_calls=$handler->calls;
		$handler->calls=array();
		$handler->$type($link, $state, $pos);
		$calls=$handler->calls;
		//----- restore calls-array
		$handler->calls=$save_calls;
		return $calls[0];
	}
	
	
?>