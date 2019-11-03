<?php

declare(strict_types=1);

// Database stuff
const DATABASE_CLASS = "MariaDbDatabase";
const DATABASE_HOST = "localhost";
const DATABASE_PATH = "sqlite.db";
const DATABASE_NAME = "beer";
const DATABASE_USER = "beer";
const DATABASE_PASS = "drinkalldaypartyallnight";
const DATABASE_DEFAULT_LIMIT = 20; // Default limit
// Webservice stuff
const WS_SHARED_SECRET = 'fasdfgh4t';
const WS_AUTH_HOSTS = ['127.0.0.1', 'localhost'];
// Input stuff
const DEFAULT_BEER_VOLUME = 500; // Volume in milliliters
const DEFAULT_BEER_TAP = 0;

$action = "showall";
$cardId = null;
$timestamp = null;
$userId = null;
$name = null;
$department = null;
$secret = null;
$verbose = false;
$tap = DEFAULT_BEER_TAP;
$volume = DEFAULT_BEER_VOLUME;

if (isCli()) {
    $options = getopt("hv", ['verbose', 'addbeer:', 'timestamp', 'setuser:', 'volume:', 'tap:', 'schema', 'clearbeers', 'clearall', 'name:', 'department:', 'help']);
    foreach ($options as $option => $value) {
        switch ($option) {
            case 'h':
            case 'help':
                showHelpAndExit();
                break;
            case 'schema':
                print MariaDbDatabase::getSchema();
                exit(0);
                break;
            case 'addbeer':
                $action = 'addbeer';
                $cardId = filter_var($value, FILTER_SANITIZE_STRING);
                break;
            case 'setuser':
                $action = 'setuser';
                $cardId = filter_var($value, FILTER_SANITIZE_STRING);
                if (!isset($options['name']) && !isset($options['department'])) {
                    die("The --setuser option needs to be combined with at least one of --name or --department\n");
                }
                break;
            case 'clearall':
                $action = 'clearall';
                break;
            case 'clearbeers':
                $action = 'clearbeers';
                break;
            case 'verbose':
                $verbose = true;
                break;
            case 'name':
            case 'department':
            case 'timestamp':
                break;
            default:
                die("Unknown option $option - see 'beer.php --help' for help on available options\n");
        }
    }
} else {
    $options = $_GET;
}
foreach ($options as $option => $value) {
    switch ($option) {
        case 'action':
            $action = filter_var($value, FILTER_SANITIZE_STRING);
            break;
        case 'cardid':
            $cardId = filter_var($value, FILTER_SANITIZE_STRING);
            break;
        case 'name':
            $name = filter_var($value, FILTER_SANITIZE_STRING);
            break;
        case 'department':
            $department = filter_var($value, FILTER_SANITIZE_STRING);
            break;
        case 'timestamp':
            $timestamp = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            break;
        case 'secret':
            $timestamp = filter_var($value, FILTER_SANITIZE_STRING);
            break;
        case 'tap':
            $tap = filter_var($value, FILTER_VALIDATE_INT);
            break;
        case 'volume':
            $volume = filter_var($value, FILTER_VALIDATE_INT);
            break;
        case 'addbeer':
        case 'setuser':
        case 'name':
        case 'department':
            break;
        default:
            LogM("Ignoring unhandled option $option");
    }
}

$db = new Database();

switch ($action) {
    case 'addbeer':
        if (isset($cardId)) {
            $db->addBeer($cardId, $volume, $tap, $timestamp);
        } else {
            die("Cannot add beer without cardId\n");
        }
        break;
    case 'setuser':
        if (isset($cardId)) {
            $db->updateUser($cardId, $name, $department);
        } else {
            die("Cannot set properties for user without cardId\n");
        }
        break;
    case 'showall':
    default:
        //print json_encode($db->getTopUsers(), JSON_PRETTY_PRINT);
        print json_encode($db->getBeerLog(), JSON_PRETTY_PRINT);
        print_r($db->getBeerLog());
        //print json_encode($db->getUser("0x0004"), JSON_PRETTY_PRINT);
}

class User {

    public $numBeers;
    public $id;
    public $cardId;
    public $name;
    public $department;

    public function __construct(string $cardId, int $id = null, string $name = null, string $department = null, int $numBeers) {
        $this->cardId = $cardId;
        $this->id = $id;
        $this->name = $name;
        $this->department = $department;
        $this->numBeers = $numBeers;
    }

}

class ExtendedUser extends User {

    public $beers = [];

    public function __construct(string $cardId, int $userId, string $userName, string $userDepartment, array $beers) {
        parent::__construct($cardId, $userId, $userName, $userDepartment, sizeof($beers));
        $this->beers = $beers;
    }

}

class Beer {

    public $id;
    public $userId;
    public $tap;
    public $volume;
    public $timestamp;

    public function __construct(int $id, int $userId, int $tap, int $volume, ?int $timestamp = null) {
        $this->id = $id;
        $this->userId = $userId;
        $this->tap = $tap;
        $this->volume = $volume;
        if (isset($timestamp)) {
            $this->timestamp = $timestamp;
        } else {
            $this->timestamp = time();
        }
    }

    public function getFormattedTimestamp(string $sFormatToday = 'h:m:s', string $sFormatOlder = 'd/m h:m:s'): string {
        if (date('YMd', $this->iTimestamp) === date('YMd')) {
            return date($sFormatToday, $this->iTimestamp);
        } else {
            return date($sFormatOlder, $this->iTimestamp);
        }
    }

}

class ExtendedBeer extends Beer {

    private $cardId;
    private $userName = "";
    private $userDepartment = "";

    public function __construct(int $beerId, int $userId, int $tap, int $volume, string $cardId, ?string $userName, ?string $userDepartment, ?int $timestamp = null) {
        parent::__construct($beerId, $userId, $tap, $volume, $timestamp);
        $this->cardId = $cardId;
        if (isset($userName)) {
            $this->userName = $userName;
        }
        if (isset($userDepartment)) {
            $this->userDepartment = $userDepartment;
        }
    }

}

class Database {

    private $dbh;

    public function __construct() {
        try {
            $this->dbh = new PDO('mysql:host=' . DATABASE_HOST . ';dbname=' . DATABASE_NAME, DATABASE_USER, DATABASE_PASS);
            $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error connecting to database: " . $e->getMessage());
        }
    }

    public function init(): void {
        print $this->getSchema(true);
    }

    public function getBeerLog(int $iNum = DATABASE_DEFAULT_LIMIT): array {
        $aBeers = [];
        try {
            logM("Getting list of last $iNum beers tapped");
            $stmt = $this->dbh->prepare('SELECT b.id, b.tap, b.volume, u.id AS userId, UNIX_TIMESTAMP(b.timestamp) AS timestamp, u.cardId, u.name, u.department FROM beers b JOIN users u ON b.userId=u.id ORDER BY b.timestamp DESC LIMIT ?');
            $stmt->bindParam(1, $iNum, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->bindColumn('id', $iBeerId, PDO::PARAM_INT);
            $stmt->bindColumn('tap', $iTap, PDO::PARAM_INT);
            $stmt->bindColumn('volume', $iVolume, PDO::PARAM_INT);
            $stmt->bindColumn('userId', $iUserId, PDO::PARAM_INT);
            $stmt->bindColumn('timestamp', $iTimestamp, PDO::PARAM_INT);
            $stmt->bindColumn('cardId', $sCardId, PDO::PARAM_STR);
            $stmt->bindColumn('name', $sName, PDO::PARAM_STR);
            $stmt->bindColumn('department', $sDepartment, PDO::PARAM_STR);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                $aBeers[] = new ExtendedBeer($iBeerId, $iUserId, $iTap, $iVolume, $sCardId, $sName, $sDepartment, $iTimestamp);
            }
        } catch (Exception $ex) {
            die("Died fetching list of beers tapped: " . $ex->getMessage() . "\n");
        }
        return $aBeers;
    }

    public function getTopUsers(int $iNum = DATABASE_DEFAULT_LIMIT): array {
        $aUsers = [];
        try {
            logM("Adding beer to log");
            $stmt = $this->dbh->prepare('SELECT u.id, u.cardId, u.name, u.department, count(*) AS numBeers FROM beers b JOIN users u ON b.userId=u.id GROUP BY u.id, u.cardId, u.name, u.department ORDER BY numBeers DESC LIMIT ?');
            $stmt->bindParam(1, $iNum, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->bindColumn('id', $iUserId, PDO::PARAM_INT);
            $stmt->bindColumn('cardId', $sCardId, PDO::PARAM_STR);
            $stmt->bindColumn('name', $sName, PDO::PARAM_STR);
            $stmt->bindColumn('department', $sDepartment, PDO::PARAM_STR);
            $stmt->bindColumn('numBeers', $iNumBeers, PDO::PARAM_INT);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                //     public function __construct(string $sUserCardId, ?int $iUserId = null, ?string $sUserName = null, ?string $sUserDepartment = null, ?int $iNumBeers) {
                $aUsers[] = new User($sCardId, $iUserId, $sName, $sDepartment, $iNumBeers);
            }
        } catch (Exception $e) {
            die("Died inserting new row in 'beer' table: " . $e->getMessage());
        }
        return $aUsers;
    }

    public function addBeer(string $sCardId, ?int $iVolume = DEFAULT_BEER_VOLUME, ?int $iTap = DEFAULT_BEER_TAP, ?int $iTimestamp = null) {
        $iUserId = null;
        if ($iVolume === null) {
            $iVolume = DEFAULT_BEER_VOLUME;
        }
        if ($iTap === null) {
            $iTap = 0;
        }

        // Query if card is already registered
        try {
            logM("Quering existing user");
            $stmt = $this->dbh->prepare('SELECT id FROM users WHERE cardId=?');
            $stmt->bindParam(1, $sCardId, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->bindColumn('id', $iUserId, PDO::PARAM_INT);
            $stmt->fetch(PDO::FETCH_BOUND);
        } catch (Exception $e) {
            die("Died looking up card id: " . $sCardId . ": " . $e->getMessage());
        }

        // Otherwise insert it
        if (!isset($iUserId)) {
            try {
                logM("Adding new user (volume = $iVolume, tap = $iTap)");
                $stmt = $this->dbh->prepare('INSERT INTO users (cardId) VALUES (?)');
                $stmt->bindParam(1, $sCardId, PDO::PARAM_STR);
                $stmt->execute();
                $iUserId = $this->dbh->lastInsertId();
            } catch (Exception $e) {
                die("Died inserting card id: " . $sCardId . ": " . $e->getMessage());
            }
        }

        // Using the ID obtained above insert a new beer into the log
        try {
            logM("Adding beer to log");
            $stmt = $this->dbh->prepare('INSERT INTO beers (userId, volume, tap, timestamp) VALUES (?,?,?,?)');
            $stmt->bindParam(1, $iUserId, PDO::PARAM_INT);
            $stmt->bindParam(2, $iVolume, PDO::PARAM_INT);
            $stmt->bindParam(3, $iTap, PDO::PARAM_INT);
            $stmt->bindParam(4, $iTimestamp, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            die("Died inserting new row in Beer table: " . $e->getMessage());
        }
    }

    public function removeBeer(int $iBeerId): bool {
        try {
            logM("Removing beer from log");
            $stmt = $this->dbh->prepare('DELETE FROM Beers WHERE beerId=?');
            $stmt->bindParam(1, $iBeerId, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            die("Died removing row in Beer table: " . $e->getMessage());
        }
    }

    public function removeUser(int $userId): bool {
        
    }

    public function updateUser(string $sCardId, ?string $sName = null, ?string $sDepartment = null): bool {
        $sSql = "UPDATE users SET";
        if (!isset($sName) && !isset($sDepartment)) {
            logM("Cannot update user - no details specified");
            return false;
        } else {
            if (isset($sName) && isset($sDepartment)) {
                $sSql .= " name = ?, department = ?";
            } else if (isset($sName)) {
                $sSql .= " name = ?";
            } else if (isset($sDepartment)) {
                $sSql .= " department = ?";
            }
        }
        $sSql .= " WHERE cardId = ?";

        try {
            logM("Updating user with cardId = $sCardId");
            $stmt = $this->dbh->prepare($sSql);
            if (isset($sName) && isset($sDepartment)) {
                $stmt->bindParam(1, $sName, PDO::PARAM_STR);
                $stmt->bindParam(2, $sDepartment, PDO::PARAM_STR);
                $stmt->bindParam(3, $sCardId, PDO::PARAM_STR);
            } else if (isset($sName)) {
                $stmt->bindParam(1, $sName, PDO::PARAM_STR);
                $stmt->bindParam(2, $sCardId, PDO::PARAM_STR);
            } else if (isset($sDepartment)) {
                $stmt->bindParam(1, $sDepartment, PDO::PARAM_STR);
                $stmt->bindParam(2, $sCardId, PDO::PARAM_STR);
            }
            $stmt->execute();
        } catch (Exception $ex) {
            die("Died updating user with cardId = $sCardId: $sSql");
        }
        return true;
    }

    public function getUser(string $sCardId): ExtendedUser {
        $iUserId = null;
        try {
            logM("Fetching user information for cardId=$sCardId");
            $stmt = $this->dbh->prepare('SELECT id, name, department, UNIX_TIMESTAMP(created) AS created, UNIX_TIMESTAMP(updated) AS updated FROM users WHERE cardId=?');
            $stmt->bindParam(1, $sCardId, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->bindColumn('id', $iUserId, PDO::PARAM_INT);
            $stmt->bindColumn('name', $sName, PDO::PARAM_STR);
            $stmt->bindColumn('department', $sDepartment, PDO::PARAM_STR);
            $stmt->bindColumn('created', $iCreated, PDO::PARAM_INT);
            $stmt->bindColumn('updated', $iUpdated, PDO::PARAM_INT);
            $stmt->fetch(PDO::FETCH_BOUND);
        } catch (Exception $ex) {
            die("Died fetching user information for cardId=$sCardId: " . $ex->getMessage());
        }

        $aBeers = [];
        try {
            logM("Fetching beer information for userId=$iUserId");
            $stmt = $this->dbh->prepare('SELECT id, tap, volume, UNIX_TIMESTAMP(timestamp) AS timestamp FROM beers WHERE userId=?');
            $stmt->bindParam(1, $iUserId, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->bindColumn('id', $iId, PDO::PARAM_INT);
            $stmt->bindColumn('tap', $iTap, PDO::PARAM_INT);
            $stmt->bindColumn('volume', $iVolume, PDO::PARAM_INT);
            $stmt->bindColumn('timestamp', $iTimestamp, PDO::PARAM_INT);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                $aBeers[] = new Beer($iId, $iUserId, $iTap, $iVolume, $iTimestamp);
            }
        } catch (Exception $ex) {
            die("Died fetching beers for cardId=$sCardId: " . $ex->getMessage());
        }



        return new ExtendedUser($sCardId, $iUserId, $sName, $sDepartment, $aBeers);
    }

    public static function getSchema(bool $bDrop = true): string {
        $sSchema = "";

        if ($bDrop) {
            $sSchema .= "DROP DATABASE IF EXISTS " . DATABASE_NAME . ";
        \n";
        }

        $sSchema .= "### Run as root ###\n"
                . "CREATE DATABASE IF NOT EXISTS " . DATABASE_NAME . ";\n"
                . "USE " . DATABASE_NAME . ";\n"
                . "GRANT ALL ON " . DATABASE_NAME . ".* TO '" . DATABASE_USER . "'@'%' IDENTIFIED BY '" . DATABASE_PASS . "';\n"
                . "FLUSH PRIVILEGES;\n\n"
                . "### users table ###\n"
                . "CREATE TABLE IF NOT EXISTS users (\n"
                . "  id INT NOT NULL AUTO_INCREMENT,\n"
                . "  cardId CHAR(8) UNIQUE NOT NULL,\n"
                . "  name VARCHAR(40) NULL,\n"
                . "  department CHAR(20) NULL,\n"
                . "  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
                . "  updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n"
                . "  PRIMARY KEY(id)"
                . ");\n\n"
                . "### beers table ###\n"
                . "CREATE TABLE IF NOT EXISTS beers (\n"
                . "  id INT NOT NULL AUTO_INCREMENT,\n"
                . "  userId INT NOT NULL,\n"
                . "  tap INT NULL,\n"
                . "  volume INT NULL,\n"
                . "  timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n"
                . "  PRIMARY KEY(id),\n"
                . "  FOREIGN KEY(userId) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE"
                . ");\n\n";
        return $sSchema;
    }

    public static function clearBeers(): void {
        $sSql = "DELETE FROM beers";
    }

}

/**
 * Simple log function
 * @param string $sMessage
 * @return void
 */
function logM(string $sMessage): void {
    $sDate = date('Y-m-d H:i:s');
    print "$sDate: $sMessage\n";
}

/**
 * Is this script initiated from CLI or HTML request?
 * @return bool
 */
function isCli(): bool {
    return php_sapi_name() === 'cli';
}

/**
 * Get IP address of calling system
 * @return string
 */
function getRemoteAddr(): string {
    if (isCli()) {
        return 'localhost';
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Print help message
 */
function showHelpAndExit() {
    print "Usage: beers.php <options>\n"
            . "  --addbeer <cardid>          Add a beer to the user identified by <cardid>\n"
            . "    --timestamp               Optional timestamp (epoch) - if not set current time will be used\n"
            . "  --setuser <cardid>          Change user properties\n"
            . "    --name <name>             Set/change user name - use quotes to include spaces\n"
            . "    --departmemt <department> Set/change user department\n"
            . "  --clearbeers                Clear all beers from database - but retain users\n"
            . "  --clearall                  Clear entire database\n"
            . "  --schema                    Print database schema\n"
            . "  --verbose                   Log whats's being done to stderr\n"
            . "  -h|--help                   This help message\n";
    exit(0);
}
