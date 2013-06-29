<?php

/**
 * database classes that are used by dw-plugins and dw-templates
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Dietrich Wittenberg <info.wittenberg@online.de>
 */

/** table - class used with dwsqlite3 - class
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Dietrich Wittenberg <info.wittenberg@online.de>
 *
 */
class dwsqlite3_table {
	function __construct($name, $table) {	// table = array(fieldname => fieldtype, ...)
		$this->tablename = $name;
		$this->table['name']=array_keys($table);
		$this->table['form']=array_values($table);
		$this->pre="";
	}

	/**
	 * creates the sql-statement to insert values $log in a table.
	 * name and form of table are statically defined in __construct - statement (client, page)
	 * values are defined in $log = array(fname1 => fval1, fname2 => fval2, ...)
	 * returns the sql-statement
	 * @param array() $log
	 * @return string
	 */
	function _mkINSERT($log) {
		// INSERT INTO tablename (fname1, fname2, ...) VALUES (fval1, fval2, ...)
		foreach ($this->table['name'] as $i => $name) {
			if (isset($log[$name])) {
				$names[$i]	=	$name;
				$items[] 		=	(strpos($this->table['form'][$i], "CHAR") !== false) ? "'".$log[$name]."'" : $log[$name];
			}
		}
		$query	=	"INSERT INTO " . $this->pre.$this->tablename	. " (" .implode(", ", $names). ") VALUES (" .implode(", ", $items).")";
		return $query;
	}
	
	/**
	 * creates the sql-statement to update values $log in a table.
	 * name and form of table are statically defined in __construct - statement (client, page)
	 * values are defined in $log = array(fname1 => fval1, fname2 => fval2, ...)
	 * returns the sql-statement without any WHERE
	 * @param array() $log
	 * @return string
	 */
	function _mkUPDATE($log) {
		// UPDATE tablename SET fname1 = fval1, fname2 = fval2, ...
		foreach ($this->table['name'] as $i => $name) {
			if (isset($log[$name])) {
				$set[$i]	=	$name . " = " . ( (strpos($this->table['form'][$i], "CHAR") !== false) ? "'".$log[$name]."'" : $log[$name] );
			}
		}
		$query	= "UPDATE " . $this->pre.$this->tablename . " SET " . implode(", ", $set);
		return $query;
	}

	/**
	 * creates the sql-statement to create a table.
	 * name and form of table are statically defined in __construct - statement
	 * returns the sql-statement
	 * @return string
	 */
	function _mkCREATE() {
		// CREATE TABLE tablename (fname1 fform1, fname2 fform2, ...)
		$names = &$this->table['name'];
		$forms = &$this->table['form'];
		foreach ($names as $i => $val) $item[] = $names[$i] . " " . $forms[$i];

		$query = "CREATE TABLE ".$this->pre. $this->tablename . " (".implode(", ", $item).")";
		return $query;
	}

	/**
	 * creates the sql-statement to drop a table.
	 * name and form of table are statically defined in __construct - statement
	 * returns the sql-statement
	 * @return string
	 */
	function _mkDROP() {
		$query = "DROP TABLE ".$this->pre.$this->tablename;
		return $query;
	}

	/**
	 * create the sql-statement to test, if a table exist.
	 * name and form of table are statically defined in __construct - statement
	 * returns the sql-statement
	 * @return string
	 */
	function _mkEXISTS() {
		$query	=	("SELECT * FROM ".$this->tablename." LIMIT 1,1");
		return $query;
	}

}


class dwsqlite3 extends SQLite3{

	function __construct($filename) {
		//$this->dbcreate($filename);
		$this->open($filename);			// open database-file and create if not exists
		$this->error=array();
	}
	
	function dbcreate($filename) {
		io_makeFileDir($filename);	// create the directory if not exists
	}

	function _table_exists($table) {
		$result = $this->query($table->_mkEXISTS());
		return $result;
	}

	function _mycreate($table) {
		$error=array();
		$result = $this->_do("create",	$table, $error); if ($result === false ) return false;
		return $result;
	}

	function _do($action, $table, &$error, $log=array()) {

		switch ($action) {
			case "create"	: $query = $table->_mkCREATE();			break;
			case "drop"		:	$query = $table->_mkDROP();				break;
			case "insert"	: $query = $table->_mkINSERT($log); break;
		}
		// execute query
		$result = $this->exec($query);
		if ($result === false ) {$this->error[] = $this->lastErrorMsg(); return false; }
		return true;
	}

	/**
	 * makes a sql-query
	 * on error: returns false and error-text in $error
	 * on success: returns result of query as an array(array())
	 *
	 * @param string $sqlquery
	 * @param array-reference $error
	 * @param unknown_type $result_type
	 * @return boolean|multitype: false = error
	 */
	function _array_query($query, &$error, $mode=SQLITE3_ASSOC) {
		// execute query
		$result = $this->query($query);
		if ($result === false ) {$error[] = $this->lastErrorMsg(); return false; }
		// create result: array(rows); rows=array(items)
		$rows = array();
		while (($res = $result->fetchArray($mode)) !== false) {$rows[]=$res;}
		return $rows;
	}

}