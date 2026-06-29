<?php
/**
 * Database Class
 * Handles all database operations using PDO
 * Version: 1.0
 */

class Database
{
    private static $instance = null;
    private $connection;
    private $stmt;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->connect();
    }

    /**
     * Get database instance (Singleton Pattern)
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Connect to database
     */
    private function connect()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        
        try {
            $this->connection = new PDO(
                $dsn,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            Logger::error('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }

    /**
     * Execute a query with parameters
     */
    public function query($sql, $params = [])
    {
        try {
            $this->stmt = $this->connection->prepare($sql);
            if (!$this->stmt->execute($params)) {
                $errorInfo = $this->stmt->errorInfo();
                throw new Exception('Database error: ' . (isset($errorInfo[2]) ? $errorInfo[2] : 'Unknown error'));
            }
            return $this;
        } catch (PDOException $e) {
            Logger::error('Query Error: ' . $e->getMessage() . ' SQL: ' . $sql);
            throw new Exception('Database query failed: ' . $e->getMessage());
        } catch (Exception $e) {
            Logger::error('Query Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Fetch a single row
     */
    public function single()
    {
        return $this->stmt->fetch();
    }

    /**
     * Fetch all rows
     */
    public function resultSet()
    {
        return $this->stmt->fetchAll();
    }

    /**
     * Get row count
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit()
    {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback()
    {
        return $this->connection->rollBack();
    }

    /**
     * Insert data
     */
    public function insert($table, $data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        $this->query($sql, array_values($data));
        return $this->lastInsertId();
    }

    /**
     * Update data
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $set = implode(', ', array_map(fn($key) => "$key = ?", array_keys($data)));
        $sql = "UPDATE $table SET $set WHERE $where";
        
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
        return $this->rowCount();
    }

    /**
     * Delete data
     */
    public function delete($table, $where, $params = [])
    {
        $sql = "DELETE FROM $table WHERE $where";
        $this->query($sql, $params);
        return $this->rowCount();
    }

    /**
     * Get connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Escape string
     */
    public function escape($string)
    {
        return $this->connection->quote($string);
    }

    /**
     * Close connection
     */
    public function closeConnection()
    {
        $this->connection = null;
    }
}
