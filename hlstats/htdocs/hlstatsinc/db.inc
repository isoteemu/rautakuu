<?php
	/*
	 * HLstats - Real-time player and clan rankings and statistics for Half-Life
	 * http://sourceforge.net/projects/hlstats/
	 *
	 * Copyright (C) 2001  Simon Garner
     * Copyright (C) 2006  Teemu A
     *                     Switched to Pear DB.
	 *
	 * This program is free software; you can redistribute it and/or
	 * modify it under the terms of the GNU General Public License
	 * as published by the Free Software Foundation; either version 2
	 * of the License, or (at your option) any later version.
	 *
	 * This program is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * You should have received a copy of the GNU General Public License
	 * along with this program; if not, write to the Free Software
	 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
	 */

if(include_once("DB.php")) {

    class hlstats_db {
        var $dns;

        var $link;
        var $result;

        var $query;

        function hlstats_db($db_addr=null, $db_user=null, $db_pass=null, $db_name=null,$db_type="mysql") {
            if ($db_addr === null) $db_addr = DB_ADDR;
            if ($db_user === null) $db_user = DB_USER;
            if ($db_pass === null) $db_pass = DB_PASS;
            if ($db_name === null) $db_name = DB_NAME;
            if ($db_host === null) $db_host = DB_HOST;

            $this->dns = array(
                'phptype'  => $db_type,
                'username' => $db_user,
                'password' => $db_pass,
                'hostspec' => $db_addr,
                'database' => $db_name,
            );

            $options = array(
                'debug'       => 2
            );

            if (DB_PCONNECT == true)
                $options['persistent'] = true;
            else
                $options['persistent'] = false;


            $this->link =& DB::Connect($this->dns, $options);

            if (PEAR::isError($this->link)) {
                $this->error("Could not connect to database server. Check that the values of DB_ADDR, DB_USER and DB_PASS in hlstats.php are set correctly. Error message was: ".$db->getMessage());
            }
        }

        function data_seek($row_number, $result=null) {
            if ($result === null) $result =& $this->result;
            $this->error("data_seek(): Not implented!");
            return false;
        }

        function &fetch_array($result=null) {
            if ($result === null) $result =& $this->result;
            return $result->fetchRow(DB_FETCHMODE_ASSOC);
        }

        function &fetch_row($result=null,$mode=DB_FETCHMODE_ORDERED) {
            if ($result === null) $result =& $this->result;
            return $result->fetchRow($mode);
        }

        function &free_result($result=null) {
            if ($result === null) $result =& $this->result;
            return $result->free();
        }

        function insert_id() {
            $this->error("insert_id(): Not implented!");
            return false;
        }

        function &num_rows($result=null){
            if ($result === null) $result =& $this->result;
            return $result->numRows();
        }

        function result($row, $field, $result=null) {
            if ($result === null) $result =& $this->result;
            $this->error("result(): Not implented!");
            return false;
        }

        function &query($query, $showerror=true)
        {
            global $db_debug;

            $this->query = $query;
            $this->result =& $this->link->query($query);

            if ($db_debug)
            {
                echo "<p><pre>$query</pre><hr></p>";
            }

            if (!$this->result)
            {
                if ($showerror)
                {
                    $this->error("Bad query.");
                }
                else
                {
                    return 0;
                }
            }

            return $this->result;
        }

        function error ($message, $exit=true) {
            error(
                "<b>Database Error</b><br>\n<br>\n" .
                "<i>Server Address:</i> {$this->dns->hostspec}<br>\n" .
                "<i>Server Username:</i> {$this->dns->user}<p>\n" .
                "<i>Error Diagnostic:</i><br>\n$message<p>\n" .
                "<i>Server Error:</i> (" . $this->link->getCode() . ") " . $this->link->getDebugInfo() . "<p>\n" .
                "<i>Last SQL Query:</i><br>\n<pre><font size=\"2\">{$this->query}</font></pre>",
                $exit
            );
        }

        function __destruct() {
            if (DB_PCONNECT != true) {
                if(is_a($this->link,"DB"))
                    $this->link->disconnect();
            }
        }
    }

    /**
     * Wrapper class for hlstats db class to pear DB class
     */

} else {

	//
	// DB_mysql
	//
	// Database abstraction class for MySQL databases.
	//
	class DB_mysql
	{
		var $db_addr;
		var $db_user;
		var $db_pass;
		var $db_name;

		var $link;
		var $result;
		var $rowdata;
		var $insert_id;
		var $numrows;
		var $query;

		function DB_mysql ($db_addr=-1, $db_user=-1, $db_pass=-1, $db_name=-1)
		{
			if ($db_addr == -1) $db_addr = DB_ADDR;
			if ($db_user == -1) $db_user = DB_USER;
			if ($db_pass == -1) $db_pass = DB_PASS;
			if ($db_name == -1) $db_name = DB_NAME;

			$this->db_addr = $db_addr;
			$this->db_user = $db_user;
			$this->db_pass = $db_pass;
			$this->db_name = $db_name;

			if (DB_PCONNECT == true)
			{
				$connectfunc = "mysql_pconnect";
			}
			else
			{
				$connectfunc = "mysql_connect";
			}

			$this->link = $connectfunc($db_addr, $db_user, $db_pass)
				or $this->error("Could not connect to database server. Check that the values of DB_ADDR, DB_USER and DB_PASS in hlstats.php are set correctly.");
			mysql_select_db($db_name, $this->link)
				or $this->error("Could not select database '$db_name'. Check that the value of DB_NAME in hlstats.php is set correctly.");
		}

		function data_seek ($row_number, $result=-1)
		{
			if ($result < 0) $result = $this->result;
			return mysql_data_seek($result, $row_number);
		}

		function fetch_array ($result=-1)
		{
			if ($result < 0) $result = $this->result;
			$this->rowdata = mysql_fetch_array($result);
			return $this->rowdata;
		}

		function fetch_row ($result=-1)
		{
			if ($result < 0) $result = $this->result;
			$this->rowdata = mysql_fetch_row($result);
			return $this->rowdata;
		}

		function free_result ($result=-1)
		{
			if ($result < 0) $result = $this->result;
			return mysql_free_result($result);
		}

		function insert_id ()
		{
			$this->insert_id = mysql_insert_id($this->link);
			return $this->insert_id;
		}

		function num_rows ($result=-1)
		{
			if ($result < 0) $result = $this->result;
			$this->numrows = mysql_num_rows($result);
			return $this->numrows;
		}

		function query ($query, $showerror=true)
		{
			global $db_debug;

			$this->query = $query;
			$this->result = mysql_query($query, $this->link);

			if ($db_debug)
			{
				echo "<p><pre>$query</pre><hr></p>";
			}

			if (!$this->result)
			{
				if ($showerror)
				{
					$this->error("Bad query.");
				}
				else
				{
					return 0;
				}
			}

			return $this->result;
		}

		function result ($row, $field, $result=-1)
		{
			if ($result < 0) $result = $this->result;

			return mysql_result($result, $row, $field);
		}

		function error ($message, $exit=true)
		{
			error(
				"<b>Database Error</b><br>\n<br>\n" .
				"<i>Server Address:</i> $this->db_addr<br>\n" .
				"<i>Server Username:</i> $this->db_user<p>\n" .
				"<i>Error Diagnostic:</i><br>\n$message<p>\n" .
				"<i>Server Error:</i> (" . mysql_errno() . ") " . mysql_error() . "<p>\n" .
				"<i>Last SQL Query:</i><br>\n<pre><font size=2>$this->query</font></pre>",
				$exit
			);
		}

		function dberror ()
		{
			return mysql_error();
		}
	}

}
?>
