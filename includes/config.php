<?php
// Migrate to SQLite and provide a thin mysqli-compatible wrapper so existing
// code (which uses $conn->prepare, bind_param, get_result, etc.) continues
// to work without rewriting every file.

// Database file
$dbFile = __DIR__ . '/../data/database.sqlite';
if (!is_dir(dirname($dbFile))) {
    mkdir(dirname($dbFile), 0755, true);
}

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    die('Failed to open SQLite DB: ' . $e->getMessage());
}

// Create tables if they don't exist (basic schema aligned with original code)
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE,
    username TEXT UNIQUE,
    password TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT,
    note_type TEXT,
    title TEXT,
    content TEXT,
    due_date TEXT,
    priority TEXT,
    is_completed INTEGER DEFAULT 0,
    color TEXT,
    created_at DATETIME DEFAULT (datetime('now','localtime'))
)");

// Compatibility layer: provide a $conn object with prepare() returning a
// statement that supports bind_param, execute, store_result, get_result.
class MysqliLikeResult {
    private $rows;
    private $idx = 0;
    public function __construct(array $rows) { $this->rows = $rows; }
    public function fetch_assoc() {
        if (isset($this->rows[$this->idx])) {
            return $this->rows[$this->idx++];
        }
        return false;
    }
    public function fetch_all() { return $this->rows; }
}

class MysqliLikeStmt {
    private $pdo;
    private $sql;
    private $params = [];
    private $resultRows = [];
    private $lastStmt = null;
    private $conn;

    public function __construct(PDO $pdo, $sql, $conn) {
        $this->pdo = $pdo;
        $this->sql = $sql;
        $this->conn = $conn;
    }

    // Mimic mysqli_stmt::bind_param("ssi", $a, $b, $c)
    public function bind_param() {
        $args = func_get_args();
        // first arg is types string, remaining are values
        array_shift($args);
        $this->params = $args;
        return true;
    }

    public function execute() {
        try {
            $stmt = $this->pdo->prepare($this->sql);
            $ok = $stmt->execute($this->params);
            $this->lastStmt = $stmt;
            // If it's a SELECT, fetch results for get_result()
            if (preg_match('/^\s*SELECT/i', $this->sql)) {
                $this->resultRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            $this->conn->lastError = null;
            return $ok;
        } catch (PDOException $e) {
            $this->conn->lastError = $e->getMessage();
            return false;
        }
    }

    public function store_result() {
        if ($this->lastStmt === null) {
            $this->execute();
        }
        return true;
    }

    public function get_result() {
        return new MysqliLikeResult($this->resultRows);
    }

    // Allow fetch of last insert id through the connection if needed
    public function insert_id() {
        return $this->pdo->lastInsertId();
    }
}

class MysqliLikeConn {
    public $pdo;
    public $lastError = null;

    public function __construct(PDO $pdo) { $this->pdo = $pdo; }
    public function prepare($sql) { return new MysqliLikeStmt($this->pdo, $sql, $this); }
    public function set_charset($cs) { /* no-op for SQLite */ }
    public function __get($name) {
        if ($name === 'error') return $this->lastError;
        return null;
    }
}

// Helper to mimic mysqli_query($conn, $sql)
function mysqli_query($conn, $sql) {
    if ($conn instanceof MysqliLikeConn) {
        // Ignore SET time_zone and similar MySQL-specific statements
        if (stripos($sql, 'SET time_zone') !== false) return true;
        try {
            $res = $conn->pdo->exec($sql);
            return $res !== false;
        } catch (PDOException $e) {
            $conn->lastError = $e->getMessage();
            return false;
        }
    }
    return false;
}

$conn = new MysqliLikeConn($pdo);

?>