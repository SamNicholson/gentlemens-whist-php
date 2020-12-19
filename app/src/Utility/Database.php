<?php


namespace App\Utility;

class Database
{

    /**
     * The MySQL database connection
     */
    private static $connection;
    /**
     * @var array
     */
    private $queryHistory = array();

    /**
     * Establishes connection to database specified, will use the deprecated Equinox constants for
     * authentication in the case that no arguments are provided, this is so that any legacy uses of this class will
     * continue to function normally
     *
     * @param string $databaseServer
     * @param string $databaseUser
     * @param string $databasePassword
     * @param string $databaseName
     */
    public function __construct($databaseServer = DB_SERVER, $databaseUser = DB_USER, $databasePassword = DB_PASS, $databaseName = DB_NAME)
    {
        //Start the connection to the database
        if (!self::$connection = mysqli_connect($databaseServer, $databaseUser, $databasePassword)) {
            if (mysqli_connect_errno()) {
                echo "Failed to connect to MySQL: " . mysqli_connect_error();
                exit();
            }
            echo 'error - database connection';
            die;
        }

        //Configure the database connection for this session
        mysqli_set_charset(self::$connection, 'utf8');
        mysqli_select_db(self::$connection, $databaseName) or die($this->getError());
    }

    /**
     * Perform a prepared statement using '?' or ':column' placeholders
     *
     * @param       $sql
     * @param array $params
     *
     * @return array
     */
    public function q($sql, array $params = [])
    {

        if ($stmt = mysqli_prepare(self::$connection, $sql)) {

            $executionParameters = [];
            $paramTypes = '';
            /* bind parameters for markers */
            foreach ($params as $param) {
                $executionParameters[] = $param;
                $paramTypes .= 's';
            }

            if (count($executionParameters)) {
                mysqli_stmt_bind_param($stmt, $paramTypes, ...$executionParameters);
            }

            /* execute query */
            mysqli_stmt_execute($stmt);

            $results = [];
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $results[] = $row;
                }
            }

            /* close statement */
            mysqli_stmt_close($stmt);
            return $results;
        }
        throw new \Exception("Query Syntax Error, query was " . $sql);
    }

    /**
     * Perform a prepared statement using '?' or ':column' placeholders
     *
     * @param       $sql
     * @param array $params
     *
     * @return array
     */
    public function getQueryString($query, array $params = [])
    {
        $keys = array();

        # build a regular expression for each parameter
        foreach ($params as &$value) {
            if (is_object($value)) {
                $value = (string) $value;
            }
            $value = '"' .$value . '"';
            $keys[] = '/[?]/';
        }

        $query = preg_replace($keys, $params, $query, 1, $count);

        #trigger_error('replaced '.$count.' keys');

        return $query;
    }

    /**
     * Performs un-prepared query. Returns false if more than one result is found, otherwise returns single array
     *
     * @param       $sql
     * @param array $params
     *
     * @return array|false single array row, or false
     */
    public function queryRow($sql, array $params = [])
    {
        if ($stmt = mysqli_prepare(self::$connection, $sql)) {

            $executionParameters = [];
            $paramTypes = '';
            /* bind parameters for markers */
            foreach ($params as $param) {
                $executionParameters[] = $param;
                $paramTypes .= 's';
            }

            if (count($executionParameters)) {
                mysqli_stmt_bind_param($stmt, $paramTypes, ...$executionParameters);
            }

            /* execute query */
            mysqli_stmt_execute($stmt);

            $results = [];
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result))
            {
                $results[] = $row;
            }

            /* close statement */
            mysqli_stmt_close($stmt);

            return count($results) === 1 ? $results[0] : false;
        }
        throw new \Exception("Query Syntax Error, query was " . $sql);
    }

    /**
     * Returns the last id inserted into the database through this connection
     *
     * @return int
     */
    public function getInsertId()
    {
        return mysqli_insert_id(self::$connection);
    }

    public function getError()
    {
        return mysqli_error(self::$connection);
    }

    public function getNumberOfRows($result)
    {
        return mysqli_num_rows($result);
    }

    public function fetchArray($result)
    {
        return mysqli_fetch_array($result);
    }

    public function getAffectedRows()
    {
        return mysqli_affected_rows(self::$connection);
    }

    public static function databaseConnected()
    {
        return mysqli_ping(self::$connection);
    }

    public static function quote($string)
    {
        return mysqli_real_escape_string(self::$connection, $string);
    }
}
