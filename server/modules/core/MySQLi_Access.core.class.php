<?php

class MySQLi_Access
{
    private $dbname = 'panel';

    public $link = null;
    public $result = null;
    public $rows = null;

    private function __construct() {}

    final private function CreateLink()
    {
        $this->link = mysqli_connect('localhost', 'panel', '1q2w3e4r', $this->dbname)
            or $this->ErrorMsg(mysqli_connect_error());

        if (mysqli_character_set_name($this->link) != 'utf8')
            if (! mysqli_set_charset($this->link, 'utf8'))
                $this->ErrorMsg(mysqli_error($this->link));
    }

    final public function Query($query)
    {
        $this->rows = null;

        if (! $this->Ping()) $this->CreateLink();

        $this->result = mysqli_query($this->link, $query, MYSQLI_STORE_RESULT)
            or $this->ErrorMsg(mysqli_error($this->link));

        if ($this->result instanceof mysqli_result)
            $this->rows = mysqli_num_rows($this->result);

        return $this->rows;
    }

    final public function FetchArray()
    {
        return $this->FetchAssoc();
    }

    final public function FetchAssoc()
    {
        return mysqli_fetch_assoc($this->result);
    }

    final public function FetchRow()
    {
        return mysqli_fetch_row($this->result);
    }

    final public function Seek($row)
    {
        if (is_numeric($row))
            mysqli_data_seek($this->result, $row);
    }

    final private function SelectDB($dbname)
    {
        if ($dbname === $this->dbname)
            return;

        if (! $this->Ping()) $this->CreateLink();

        mysqli_select_db($this->link, $dbname)
            or $this->ErrorMsg(mysqli_error($this->link));

        $this->dbname = $dbname;
    }

    final public function GetLastID()
    {
        return ($this->link instanceof mysqli) ? mysqli_insert_id($this->link) : false;
    }

    final public function EscapeString($str)
    {
        if ($str === '' OR ! is_string($str))
            return $str;

        // does not work well ... need to do more research
        // perhaps only nessassary in a multilingual database.
        // with english only ... addslashes() does just fine.
        // Characters encoded are NUL (ASCII 0), \n, \r, \, ', ", and Control-Z.
        // return mysqli_real_escape_string($this->link, $str);

        // single quote ('), double quote ("), backslash (\) and NUL (the NULL byte).
        return addslashes($str);
    }

    final public function EscStr($str)
    {
        return $this->EscapeString($str);
    }

    final public function EscString($str)
    {
        return $this->EscapeString($str);
    }

    final public function Ping()
    {
        return ($this->link instanceof mysqli) ? mysqli_ping($this->link) : false;
    }

    final public function GetDBName()
    {
        return $this->dbname;
    }

    final private function Close()
    {
        if ($this->Ping()) mysqli_close($this->link);
        $this->link = null;
    }

    final public function OptimizeTables($verbose = 0)
    {
        $this->DoTables('OPTIMIZE', $verbose);
    }

    final public function RepairTables($verbose = 0)
    {
        $this->DoTables('REPAIR', $verbose);
    }

    final private function ErrorMsg($error)
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        file_put_contents(PANEL_BASE_PATH . '/server/logs/mysqli_log', $trace, FILE_APPEND);

        $this->Close();
        die('Error detected in MySQLi. Our technicians are on it!');
    }

    final private function DoTables($operation, $verbose)
    {
        $operation = strtoupper($operation);

        if (! in_array($operation, array('OPTIMIZE','REPAIR')))
            exit("ERROR: invalid operation:{$operation} in MySQL_Access()->DoTables()");

        if (! $this->Ping()) $this->CreateLink();

        $result = mysqli_query($this->link, 'SHOW TABLES');

        if (mysqli_num_rows($result))
        {
            while(list($table) = mysqli_fetch_row($result))
            {
                if ($verbose) echo "$operation table: {$table} ... ";
                mysqli_query($this->link, "$operation TABLE {$table}");
                if ($verbose) echo "done\n";
            }
        }
        else if ($verbose)
        {
             echo "No tables found to $operation.\n";
        }
    }

    function __destruct()
    {
        $this->Close();
    }
};

?>