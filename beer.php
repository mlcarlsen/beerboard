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

$sAction = "showall";
$sCardId = null;
$iTimestamp = null;
$sUserId = null;
$sName = null;
$sDepartment = null;
$sSecret = null;
$bVerbose = false;
$iTap = DEFAULT_BEER_TAP;
$iVolume = DEFAULT_BEER_VOLUME;
$iNum = DATABASE_DEFAULT_LIMIT;

if (isCli()) {
    $aOptions = getopt("hv", ['verbose', 'addbeer:', 'timestamp', 'setuser:', 'volume:', 'tap:', 'schema', 'topusers', 'beerlog', 'clearbeers', 'clearall', 'name:', 'department:', 'help']);
    foreach ($aOptions as $sOption => $sValue) {
        switch ($sOption) {
            case 'addbeer':
                $sAction = 'addbeer';
                $sCardId = filter_var($sValue, FILTER_SANITIZE_STRING);
                break;
            case 'beerlog':
                $sAction = 'beerlog';
                break;
            case 'clearall':
                $sAction = 'clearall';
                break;
            case 'clearbeers':
                $sAction = 'clearbeers';
                break;
            case 'department':
                $sDepartment = filter_var($sValue, FILTER_SANITIZE_STRING);
                if (!isset($aOptions['setuser'])) {
                    die("The --department option can only be used in combination with the --setuser option\n");
                }
                break;
            case 'h':
            case 'help':
                showHelpAndExit();
                break;
            case 'name':
                $sName = filter_var($sValue, FILTER_SANITIZE_STRING);
                if (!isset($aOptions['setuser'])) {
                    die("The --name option can only be used in combination with the --setuser option\n");
                }
                break;
            case 'num':
                $iNum = filter_var($sValue, FILTER_VALIDATE_INT);
                if(!isset($aOptions['beerlog']) && !isset($aOptions['topusers'])) {
                    die("The --num option can only be used in combination with the --beerlog or --topusers option\n");
                }
                break;
            case 'schema':
                print Database::getSchema();
                exit(0);
                break;
            case 'setuser':
                $sAction = 'setuser';
                $sCardId = filter_var($sValue, FILTER_SANITIZE_STRING);
                if (!isset($aOptions['name']) && !isset($aOptions['department'])) {
                    die("The --setuser option needs to be combined with at least one of --name or --department\n");
                }
                break;
            case 'tap':
                $iTap = filter_var($sValue, FILTER_VALIDATE_INT);
                if (!isset($aOptions['addbeer'])) {
                    die("The --tap option can only be used in combination with the --addbeer option\n");
                }
                break;
            case 'timestamp':
                $iTimestamp = filter_var($sValue, FILTER_VALIDATE_INT);
                break;
            case 'topusers':
                $sAction = 'topusers';
                break;
            case 'verbose':
                $bVerbose = true;
                break;
            case 'volume':
                $iVolume = filter_var($sValue, FILTER_VALIDATE_INT);
                break;
            default:
                die("Unknown option $sOption - see 'beer.php --help' for help on available options\n");
        }
    }

    // Init database
    $oDb = new Database();

    switch ($sAction) {
        case 'addbeer':
            if (isset($sCardId)) {
                $oDb->addBeer($sCardId, $iVolume, $iTap, $iTimestamp);
            } else {
                die("Cannot add beer without cardId\n");
            }
            break;
        case 'beerlog':
            print json_encode($oDb->getBeerLog($iNum), JSON_PRETTY_PRINT);
            break;
        case 'clear-all':
            $oDb->clearAll();
            break;
        case 'clear-beers':
            $oDb->clearBeers();
            break;
        case 'setuser':
            if (isset($sCardId)) {
                $oDb->updateUser($sCardId, $sName, $sDepartment);
            } else {
                die("Cannot set properties for user without cardId\n");
            }
            break;
        case 'topusers':
            print json_encode($oDb->getTopUsers($iNum), JSON_PRETTY_PRINT);
            break;
        default:
            print json_encode($oDb->getTopUsers($iNum), JSON_PRETTY_PRINT);
            print json_encode($oDb->getBeerLog($iNum), JSON_PRETTY_PRINT);
    }
}

/**
 * No Hungarian notation on member variable, so we can convert objets to JSON directly
 */
class User {

    public $numBeers;
    public $id;
    public $cardId;
    public $name;
    public $department;
    public $totalVolume = 0;

    public function __construct(string $sCardId, ?int $iId, ?string $sName, ?string $sDepartment, ?int $iNumBeers, ?int $iTotalVolume = null) {
        $this->cardId = $sCardId;
        $this->id = $iId;
        $this->name = $sName;
        $this->department = $sDepartment;
        $this->numBeers = $iNumBeers;
        $this->totalVolume = $iTotalVolume;
    }

}

class ExtendedUser extends User {

    public $beers = [];

    public function __construct(string $sCardId, int $iId, string $sName, string $sDepartment, array $beers) {
        parent::__construct($sCardId, $iId, $sName, $sDepartment, sizeof($beers));
        $this->beers = $beers;
        foreach($beers as $beer) {
            $this->Totalvolume += $beer->volume;
            print_r($beer);
        }
    }

}

class Beer {

    public $id;
    public $userId;
    public $tap;
    public $volume;
    public $timestamp;

    public function __construct(int $iId, int $iUserId, int $iTap, int $iVolume, ?int $iTimestamp = null) {
        $this->id = $iId;
        $this->userId = $iUserId;
        $this->tap = $iTap;
        $this->volume = $iVolume;
        if (isset($iTimestamp)) {
            $this->timestamp = $iTimestamp;
        } else {
            $this->timestamp = time();
        }
    }

    public function getFormattedTimestamp(string $sFormatToday = 'h:m:s', string $sFormatOlder = 'd/m h:m:s'): string {
        if (date('YMd', $this->timestamp) === date('YMd')) {
            return date($sFormatToday, $this->timestamp);
        } else {
            return date($sFormatOlder, $this->timestamp);
        }
    }

}

class ExtendedBeer extends Beer {

    public $cardId;
    public $userName = "N/A";
    public $userDepartment = "N/A";

    public function __construct(int $iBeerId, int $iUserId, int $tap, int $iVolume, string $sCardId, ?string $sUserName, ?string $sUserDepartment, ?int $iTimestamp = null) {
        parent::__construct($iBeerId, $iUserId, $tap, $iVolume, $iTimestamp);
        $this->cardId = $sCardId;
        if (isset($sUserName)) {
            $this->userName = $sUserName;
        }
        if (isset($sUserDepartment)) {
            $this->userDepartment = $sUserDepartment;
        }
    }

}

class Tap {
    public $tap;
    public $numBeers;
    public $totalVolume;
    
    public function __construct(int $iTap, int $iNumBeers, int $iTotalVolume) {
        $this->tap = $iTap;
        $this->numBeers = $iNumBeers;
        $this->totalVolume = $iTotalVolume;
    }
            
}

class Database {

    private $oDb;

    public function __construct() {
        try {
            $this->oDb = new PDO('mysql:host=' . DATABASE_HOST . ';dbname=' . DATABASE_NAME, DATABASE_USER, DATABASE_PASS);
            $this->oDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Error connecting to database: " . $e->getMessage());
        }
    }

    public function getBeerLog(?int $iNum): array {
        if (!is_int($iNum)) {
            $iNum = DATABASE_DEFAULT_LIMIT;
        }

        $aBeers = [];
        try {
            logM("Getting list of last $iNum beers tapped");
            $stmt = $this->oDb->prepare('SELECT b.id, b.tap, b.volume, u.id AS userId, u.cardId, u.name, u.department, UNIX_TIMESTAMP(b.timestamp) AS timestamp FROM beers b JOIN users u ON b.userId=u.id ORDER BY b.timestamp DESC LIMIT ?');
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

    public function getTopUsers(?int $iNum, ?string $sOrderBy): array {
        if (!is_int($iNum)) {
            $iNum = DATABASE_DEFAULT_LIMIT;
        }
        if (!isset($sOrderBy) || $sOrderBy === 'number') {
            $sOrderBy = 'numBeers';
        } else if($sOrderBy === 'volume') {
            $sOrderBy = 'totalVolume';
        }
        

        $aUsers = [];
        try {
            logM("Adding beer to log");
            $stmt = $this->oDb->prepare('SELECT u.id, u.cardId, u.name, u.department, count(*) AS numBeers, sum(b.volume) AS totalVolume FROM beers b JOIN users u ON b.userId=u.id GROUP BY u.id, u.cardId, u.name, u.department ORDER BY ' . $sOrderBy . ' DESC LIMIT ?');
            $stmt->bindParam(1, $iNum, PDO::PARAM_INT);
            $stmt->execute();
            $stmt->bindColumn('id', $iUserId, PDO::PARAM_INT);
            $stmt->bindColumn('cardId', $sCardId, PDO::PARAM_STR);
            $stmt->bindColumn('name', $sName, PDO::PARAM_STR);
            $stmt->bindColumn('department', $sDepartment, PDO::PARAM_STR);
            $stmt->bindColumn('numBeers', $iNumBeers, PDO::PARAM_INT);
            $stmt->bindColumn('totalVolume', $iTotalVolume, PDO::PARAM_INT);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                //     public function __construct(string $sUserCardId, ?int $iUserId = null, ?string $sUserName = null, ?string $sUserDepartment = null, ?int $iNumBeers) {
                $aUsers[] = new User($sCardId, $iUserId, $sName, $sDepartment, $iNumBeers, $iTotalVolume);
            }
        } catch (Exception $e) {
            die("Died inserting new row in 'beer' table: " . $e->getMessage());
        }
        return $aUsers;
    }
    
    public function getTimeDistribution() {
        
    }
    
    public function getTapDistribution(): array {
        $aTapDistribution = [];
        try {
            $stmt = $this->oDb->prepare('SELECT tap, count(*) as numBeers, sum(volume) AS totalVolume FROM beers GROUP BY tap');
            $stmt->execute();
            $stmt->bindColumn('tap', $iTap, PDO::PARAM_INT);
            $stmt->bindColumn('numBeers', $iNumBeers, PDO::PARAM_INT);
            $stmt->bindColumn('totalVolume', $iTotalVolume, PDO::PARAM_INT);
            while ($stmt->fetch(PDO::FETCH_BOUND)) {
                $aTapDistribution[] = new Tap($iTap, $iNumBeers, $iTotalVolume);
            }
        } catch (Exception $e) {
            die("Died getting tap distribution: " . $e->getMessage());
        }
        return $aTapDistribution;
    }

    public function addBeer(string $sCardId, ?int $iVolume, ?int $iTap, ?int $iTimestamp): bool {
        if (!is_int($iVolume)) {
            $iVolume = DEFAULT_BEER_VOLUME;
        }
        if (!is_int($iTap)) {
            $iTap = DEFAULT_BEER_TAP;
        }
        if (!is_int($iTimestamp)) {
            $iTimestamp = null;
        }

        $iUserId = null;

        // Query if card is already registered
        try {
            logM("Quering existing user");
            $stmt = $this->oDb->prepare('SELECT id FROM users WHERE cardId=?');
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
                $stmt = $this->oDb->prepare('INSERT INTO users (cardId) VALUES (?)');
                $stmt->bindParam(1, $sCardId, PDO::PARAM_STR);
                $stmt->execute();
                $iUserId = $this->oDb->lastInsertId();
            } catch (Exception $e) {
                die("Died inserting card id: " . $sCardId . ": " . $e->getMessage());
            }
        }

        // Using the ID obtained above insert a new beer into the log
        try {
            logM("Adding beer to log");
            $stmt = $this->oDb->prepare('INSERT INTO beers (userId, volume, tap, timestamp) VALUES (?,?,?,?)');
            $stmt->bindParam(1, $iUserId, PDO::PARAM_INT);
            $stmt->bindParam(2, $iVolume, PDO::PARAM_INT);
            $stmt->bindParam(3, $iTap, PDO::PARAM_INT);
            $stmt->bindParam(4, $iTimestamp, PDO::PARAM_INT);
            $stmt->execute();
        } catch (Exception $e) {
            die("Died inserting new row in Beer table: " . $e->getMessage());
        }
        return true;
    }

    public function removeBeer(int $iBeerId): bool {
        try {
            logM("Removing beer from log");
            $stmt = $this->oDb->prepare('DELETE FROM Beers WHERE beerId=?');
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
            $stmt = $this->oDb->prepare($sSql);
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

    public function getUser(string $sCardId): ?ExtendedUser {
        $iUserId = null;
        try {
            logM("Fetching user information for cardId=$sCardId");
            $stmt = $this->oDb->prepare('SELECT id, name, department, UNIX_TIMESTAMP(created) AS created, UNIX_TIMESTAMP(updated) AS updated FROM users WHERE cardId=?');
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
            $stmt = $this->oDb->prepare('SELECT id, tap, volume, UNIX_TIMESTAMP(timestamp) AS timestamp FROM beers WHERE userId=?');
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
        $sSchema = "### Run as root ###\n";

        if ($bDrop) {
            $sSchema .= "DROP DATABASE IF EXISTS " . DATABASE_NAME . ";
        \n";
        }

        $sSchema .= "CREATE DATABASE IF NOT EXISTS " . DATABASE_NAME . ";\n"
                . "USE " . DATABASE_NAME . ";\n"
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
                . ");\n\n"
                . "### create users ###\n"
                . "GRANT ALL ON " . DATABASE_NAME . ".* TO '" . DATABASE_USER . "'@'%' IDENTIFIED BY '" . DATABASE_PASS . "';\n"
                . "FLUSH PRIVILEGES;\n\n";


        return $sSchema;
    }

    public static function clearBeers(): void {
        $num = $this->oDb->exec("DELETE FROM beers");
        if ($num === false) {
            die("Could not clear beers from database\n");
        } else {
            logM("Delete $num beers from database");
        }
    }

    public static function clearAll(): void {
        clearBeers();
        $num = $this->oDb->exec("DELETE FROM users");
        if ($num === false) {
            die("Could not clear users from database\n");
        } else {
            logM("Delete $num users from database");
        }
    }

}

/**
 * Simple log function
 * @param string $sMessage
 * @return void
 */
function logM(string $sMessage): void {
    global $bVerbose;
    if ($bVerbose && isCli()) {
        $sDate = date('Y-m-d H:i:s');
        print "$sDate: $sMessage\n";
    }
}

/**
 * 
 * @return type
 */
function isCli() {
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
            . "    --tap                     Set tap number - default is " . DEFAULT_BEER_TAP . "\n"
            . "    --timestamp               Optional timestamp (epoch) - if not set current time will be used\n"
            . "  --setuser <cardid>          Change user properties\n"
            . "    --name <name>             Set/change user name - use quotes to include spaces\n"
            . "    --departmemt <department> Set/change user department\n"
            . "  --clearbeers                Clear all beers from database - but retain users\n"
            . "  --clearall                  Clear entire database\n"
            . "  --showall                   Show all data\n"
            . "  --schema                    Print database schema\n"
            . "  --verbose                   Log whats's being done to stderr\n"
            . "  -h|--help                   This help message\n";
    exit(0);
}
