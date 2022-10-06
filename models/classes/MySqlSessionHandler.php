<?php
/**
* A PHP session handler to keep session data within a MySQL database
*
* @author 	Manuel Reinhard <manu@sprain.ch>
* @link		https://github.com/sprain/PHP-MySQL-Session-Handler
*/
class MySqlSessionHandler{
    /**
     * a database MySQLi connection object
     */
    private static $dbConnection = false;
    
  
    /**
     * Open the database.
     */
    public function openDB()
    {
        if (!self::$dbConnection) {
            self::$dbConnection = new mysqli(WaseUtil::getParm('HOST'), WaseUtil::getParm('USER'), WaseUtil::getParm('PASS'), WaseUtil::getParm('DATABASE'));
            if (mysqli_connect_error()) {
                throw new Exception('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
            }
        }
        
        
    }
    
    /**
     * Open the session
     * @return bool
     */
    public function open()
    {
        //delete old session handlers
        $limit = time() - (3600 * 24);
        self::openDB()();
        $sql = sprintf("DELETE FROM %s WHERE timestamp < %s", $this->dbTable, $limit);
        return self::$dbConnection->query($sql);
    }
    /**
     * Close the session
     * @return bool
     */
    public function close()
    {
        self::openDB();
        return self::$dbConnection->close();
    }
    /**
     * Read the session
     * @param int session id
     * @return string string of the sessoin
     */
    public function read($id)
    {
        self::openDB();
        $sql = sprintf("SELECT data FROM %s WHERE id = '%s'", $this->dbTable, self::$dbConnection->escape_string($id));
        if ($result = self::$dbConnection->query($sql)) {
            if ($result->num_rows && $result->num_rows > 0) {
                $record = $result->fetch_assoc();
                return $record['data'];
            } else {
                return false;
            }
        } else {
            return false;
        }
        return true;
    }
    /**
     * Write the session
     * @param int session id
     * @param string data of the session
     */
    public function write($id, $data)
    {
        self::openDB();
        $sql = sprintf("REPLACE INTO %s VALUES('%s', '%s', '%s')",
                       $this->dbTable,
                       self::$dbConnection->escape_string($id),
                       self::$dbConnection->escape_string($data),
                       time());
        return self::$dbConnection->query($sql);
    }
    /**
     * Destoroy the session
     * @param int session id
     * @return bool
     */
    public function destroy($id)
    {
        self::openDB();
        $sql = sprintf("DELETE FROM %s WHERE `id` = '%s'", $this->dbTable, self::$dbConnection->escape_string($id));
        return self::$dbConnection->query($sql);
    }
    /**
     * Garbage Collector
     * @param int life time (sec.)
     * @return bool
     * @see session.gc_divisor      100
     * @see session.gc_maxlifetime 1440
     * @see session.gc_probability    1
     * @usage execution rate 1/100
     *        (session.gc_probability/session.gc_divisor)
     */
    public function gc($max)
    {
        self::openDB();
        $sql = sprintf("DELETE FROM %s WHERE `timestamp` < '%s'", $this->dbTable, time() - intval($max));
        return self::$dbConnection->query($sql);
    }
}
?>