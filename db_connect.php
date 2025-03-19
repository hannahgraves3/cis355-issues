<?php

class Database {

    // Database connection details
    private static $dbName = 'cis355'; 
    private static $dbHost = 'localhost';
    private static $dbUsername = 'root';
    private static $dbUserPassword = '';
    private static $connection = null;

    // Prevent instantiation
    public function __construct() {
        exit('No constructor required for class: Database');
    }

    // Establish database connection
    public static function connect() {
        if (self::$connection === null) {
            try {
                self::$connection = new PDO(
                    "mysql:host=" . self::$dbHost . ";dbname=" . self::$dbName . ";charset=utf8mb4",
                    self::$dbUsername,
                    self::$dbUserPassword
                );
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    // Close connection
    public static function disconnect() {
        self::$connection = null;
    }

}

?>
