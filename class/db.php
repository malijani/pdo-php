<?php
namespace db;

// Enable Logging.
require_once "log.php";

class DB
{
    // @object, The PDO object
    private $pdo;

    // @object, The PDO statement object
    private $stmt;

    // @array, The database settings
    private $settings;

    // @bool, Database connection
    private $connected = false;

    // @object, Object for logging exceptions
    private $log;

    // @array, The parameters of the SQL query
    private $parameters;


    /*
     * Default Constructor
     * 1. Instantiate Log class
     * 2. Connect to database
     * 3. Creates the parameters array
     * */
    public function __construct()
    {
        $this->log = new Log();
        $this->connect();
        $this->parameters = array();
    }


    /*
     * Default Destructor
     * 1. Close PDO connection
     * */
    public function __destruct()
    {
        // Set the PDO object to null to close the connection
        $this->pdo = null;
    }


    /*
     * This method makes connection to the database
     * 1. Reads the database settings from an ini file
     * 2. Puts the ini content into the settings array
     * 3. Tries to connect to the database
     * 4. If connection failed, exception is displayed and a Log file gets created
     * */
    private function connect()
    {
        $this->settings = parse_ini_file("config.php");
        $dsn = 'mysql:hostname='.$this->settings['host'].';dbname='.$this->settings['dbname'];
        try {
            // Read settings from ini file, set UTF8
            $this->pdo = new \PDO($dsn, $this->settings['user'], $this->settings['password']);

            // We can now log any exceptions on Fatal errors
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Disable emulation of prepared statements, user REAL prepared statements instead
            $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);

            // Connection succeeded, set the boolean to true
            $this->connected = true;
        } catch (\PDOException $e) {
            // Write error into log file
            echo $this->ExceptionLog($e->getMessage());
            die();
        }
    }


    /*
     * This method initializes the query and parameters
     *
     * 1. If not connected, connect to the database
     * 2. Prepare Query
     * 3. Parametrize Query
     * 4. Execute Query
     * 5. On exception: Write Exception into the log + SQL query
     * 6. Reset the Parameters.
     * */
    private function init($query, $parameters="")
    {
        // Connect to database
        if (!$this->connected) {
            $this->connect();
        }

        try {
            // Prepare query
            $this->stmt = $this->pdo->prepare($query);

            // Add parameters to parameter array
            $this->bindMore($parameters);

            // Bind parameters
            if (!empty($this->parameters)) {
                foreach ($this->parameters as $param=>$value) {
                    if (is_int($value[1])) {
                        $type = \PDO::PARAM_INT;
                    } elseif (is_bool($value[1])) {
                        $type = \PDO::PARAM_BOOL;
                    } elseif (is_null($value[1])) {
                        $type = \PDO::PARAM_NULL;
                    } else {
                        $type = \PDO::PARAM_STR;
                    }
                    // Add type when binding values to the column
                    $this->stmt->bindValue($value[0], $value[1], $type);
                }

                // Execute SQL
                $this->stmt->execute();
            }
        } catch (\PDOException $e) {
            // Write into log file and display Exception
            echo $this->ExceptionLog($e->getMessage(), $query);
            die();
        }

        // Reset the parameters
        $this->parameters = array();
    }


    /*
     * @void
     *
     * Add the parameter to the parameter array
     * @param string $para
     * @param string $value
     * */
    public function bind($para, $value)
    {
        $this->parameters[sizeof($this->parameters)] = [":" . $para , $value];
    }


    /*
     * @void
     *
     * Add more parameters to the parameter array
     * @param array $pArray
     * */
    public function bindMore($pArray)
    {
        if (empty($this->parameters) && is_array($pArray)) {
            $columns = array_keys($pArray);
            foreach ($columns as $i=>&$column) {
                $this->bind($column, $pArray[$column]);
            }
        }
    }


    /*
     * If the SQL query contains a SELECT or SHOW statement it returns an array containing all of the result set row
     * If ths SQL statement is a DELETE, INSERT or UPDATE statement it returns the number of affected rows
     *
     * @param string $query
     * @param array $params
     * @return mixed
     * */
    public function query($query, $params=null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        $query = trim(str_replace("\r", " " , $query));

        $this->init($query, $params);

        $rawStatement = explode(" ", preg_replace("/\s+|\t+|\n+/", " ", $query));
        
        // Which SQL statement is used
        $statement = strtolower($rawStatement[0]);
        if ($statement==='select' || $statement==='show') {
            return $this->stmt->fetchAll($fetchMode);
        } elseif ($statement==='insert' || $statement==='update' || $statement==='delete') {
            return $this->stmt->rowCount();
        } else {
            return NULL;
        }
    }


    /*
     * Returns the last inserted id
     *
     * @return string
    */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }


    #### TRANSACTIONS
    /*
     * Starts the transaction
     *
     * @return boolean, true on success or false on failure
     * */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /*
     * Execute transaction
     *
     * @return boolean, true on success or false on failure
     * */
    public function executeTransaction()
    {
        $this->pdo->executeTransaction();
    }

    /*
     * Rollback of transaction
     *
     * @return boolean, true on success or false on failure
     * */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }


    #### QUERIES ON ROWS AND COLUMNS
    /*
     * Returns an array which represents a column from the result set
     *
     * @param string $query
     * @param array $params
     * @return array
     * */
    public function column($query, $params = null)
    {
        $this->init($query, $params);
        $Columns = $this->stmt->fetchAll(\PDO::FETCH_NUM);

        $column = null;
        foreach ($Columns as $cells) {
            $column[] = $cells[0];
        }

        return $column;
    }

    /*
     * Returns an array which represents a row from the result set
     *
     * @param string $query
     * @param array $params
     * @param int $fetchMode
     * @return array
     * */
    public function row($query, $params = null, $fetchMode = \PDO::FETCH_ASSOC)
    {
        $this->init($query, $params);
        $result = $this->stmt->fetch($fetchMode);
        // Frees up the connection to the server so that other SQL statements may be issued
        $this->stmt->closeCursor();
        return $result;
    }


    /*
     * Return the value of one single field/column
     *
     * @param string $query
     * @param array $params
     * @return string
     * */
    public function single($query, $params=null)
    {
        $this->init($query,$params);
        $result = $this->stmt->fetchColumn();
        // Frees up the connection to the server so that other SQL statements may be issued
        $this->stmt->closeCursor();
        return $result;
    }


    #### EXCEPTIONS
    /*
     * Writes the log and returns the exception
     *
     * @param string $message
     * @param string $sql
     * @return string
     * */
    private function ExceptionLog($message, $sql = "")
    {
        $exception = 'Unhandled Exception. <br>';
        $exception .= $message;
        $exception .= "<br>You can find the error back in the log.";

        if (!empty($sql)) {
            // Add the Raw SQL to the Log
            $message .= "\r\nRaw SQL: " . $sql;
        }
        // Write into log file
        $this->log->write($message);
        return $exception;
    }


}

