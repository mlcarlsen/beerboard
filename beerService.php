<?php

declare(strict_types=1);

require_once('beer.php');
header('Content-type: application/json');

// Init DB
$oDb = new Database();

// Get request method
$sMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');

// Handle action for GET and POST requests
if ($sMethod === 'GET') {
    $sAction = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
    switch ($sAction) {
        case 'beerlog':
            $iNum = filter_input(INPUT_GET, 'num', FILTER_VALIDATE_INT);
            if ($iNum === false) {
                sendJsonStatusResponse(false, 'Num must be specified as an integer');
            } else {
                $aBeers = $oDb->getBeerLog($iNum);
                if ($aBeers === null) {
                    sendJsonStatusResponse(false, 'Could not fetch beer log');
                } else {
                    sendJsonResponse($aBeers);
                }
            }
            break;
        case 'topusers':
            $iNum = filter_input(INPUT_GET, 'num', FILTER_VALIDATE_INT);
            if ($iNum === false) {
                sendJsonStatusResponse(false, 'Num must be specified as an integer');
            } else {
                $aUsers = $oDb->getTopUsers($iNum);
                if ($aUsers === null) {
                    sendJsonStatusResponse(false, 'Could not get list of users');
                } else {
                    sendJsonResponse($aUsers);
                }
            }
            break;
        case 'getuser':
            $sCardId = filter_input(INPUT_GET, 'cardid');
            if ($sCardId === null || $sCardId === false) {
                sendJsonStatusResponse(false, 'Must provide cardid param');
            } else {
                $oUser = $oDb->getUser($sCardId);
                if ($oUser === null) {
                    sendJsonStatusResponse(false, "Could not find user with cardId '$sCardId'");
                } else {
                    sendJsonResponse($oUser);
                }
            }
            break;
        default:
            sendJsonResponse(false, "Unknown action '$sAction'");
    }
} else if ($sMethod === 'POST') {
    $sAction = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    switch ($sAction) {
        case 'addbeer':
            $sCardId = filter_input(INPUT_POST, 'cardid', FILTER_SANITIZE_STRING);
            $iVolume = filter_input(INPUT_POST, 'volume', FILTER_VALIDATE_INT);
            $iTap = filter_input(INPUT_POST, 'tap', FILTER_VALIDATE_INT);
            $iTimestamp = filter_input(INPUT_POST, 'timestamp', FILTER_VALIDATE_INT);
            if ($sCardId === null || $sCardId === false) {
                sendJsonStatusResponse(false, 'Must provide cardid param');
            } else if ($iVolume === false) {
                sendJsonStatusResponse(false, 'Volume must be specified as an integer (ml)');
            } else if ($iTap === false) {
                sendJsonStatusResponse(false, "Tap id must be specified as an integer");
            } else if ($iTimestamp === false) {
                sendJsonStatusResponse(false, 'Timestamp must be specified as an integer (UNIX epoch)');
            } else if ($oDb->addBeer($sCardId, $iVolume, $iTap, $iTimestamp)) {
                sendJsonStatusResponse(true, 'Added beer');
            } else {
                sendJsonStatusResponse(false, 'Could not add beer');
            }
            break;
        case 'updateUser':
            $sCardId = filter_input(INPUT_POST, 'cardid', FILTER_SANITIZE_STRING);
            $sName = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $sDepartment = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
            if ($oDb->updateUser($sCardId, $sName, $sDepartment)) {
                sendJsonStatusResponse(true, 'Updated user');
            } else {
                sendJsonStatusResponse(false, 'Could not update user');
            }
            break;
        case '':
            sendJsonStatusResponse(false, 'Action is empty');
            break;
        default:
            sendJsonStatusResponse(false, "Unknown action '$sAction'");
    }
} else {
    sendJsonStatusResponse(false, "Method '$sMethod' is not available");
}

/**
 * Send status encoded as JSON
 * @param bool $bSuccess
 * @param string $sMessage
 * @return void
 */
function sendJsonStatusResponse(bool $bSuccess, string $sMessage): void {
    sendJsonResponse(['success' => ($bSuccess ? 1 : 0), 'message' => $sMessage]);
    return;
}

/**
 * Send requested result as JSON encoded string
 * @param type $oResponse
 * @return void
 */
function sendJsonResponse($oResponse): void {
    print json_encode($oResponse, JSON_PRETTY_PRINT);
    return;
}
