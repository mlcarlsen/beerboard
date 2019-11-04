<?php

declare(strict_types=1);

require_once('beer.php');
header('Content-type: application/json');

$db = new Database();

$method = filter_input(INPUT_SERVER, 'REQUEST_METHOD');

if ($method === 'GET') {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);

    switch ($action) {
        case 'beerlog':
            $num = filter_input(INPUT_GET, 'num', FILTER_VALIDATE_INT);
            if ($num === false) {
                print json_encode(['success' => 0, 'message' => "Num must be specified as an integer"], JSON_PRETTY_PRINT);
            } else {
                print json_encode($db->getBeerLog($num), JSON_PRETTY_PRINT);
            }
            break;
        case 'topusers':
            $num = filter_input(INPUT_GET, 'num', FILTER_VALIDATE_INT);
            if ($num === false) {
                print json_encode(['success' => 0, 'message' => "Num must be specified as an integer"], JSON_PRETTY_PRINT);
            } else {
                print json_encode($db->getTopUsers($num), JSON_PRETTY_PRINT);
            }
            break;
        case 'getuser':
            $cardId = filter_input(INPUT_GET, 'cardid');
            if($cardId === null || $cardId === false) {
                print json_encode(['success' => 0, 'message' => "Must provide cardid param"], JSON_PRETTY_PRINT);
            } else {
                print json_encode($db->getUser($cardId), JSON_PRETTY_PRINT);
            }
            break;
        default:
            print json_encode(['success' => 0, 'message' => "Unknown action '$action'"], JSON_PRETTY_PRINT);
    }
} else if($method === 'POST') {
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    switch($action) {
        case 'addbeer':
            $cardId = filter_input(INPUT_POST, 'cardid', FILTER_SANITIZE_STRING);
            if($cardId === null ||  $cardId === false) {
                print json_encode(['success' => 0, 'message' => "Must provide cardid param"], JSON_PRETTY_PRINT);
            }
            $volume = filter_input(INPUT_POST, 'volume', FILTER_VALIDATE_INT);
            if($volume === false) {
                print json_encode(['success' => 0, 'message' => "Volume must be specified as an integer (ml)"], JSON_PRETTY_PRINT);
            }
            $tap = filter_input(INPUT_POST, 'tap', FILTER_VALIDATE_INT);
            if($tap === false) {
                print json_encode(['success' => 0, 'message' => "Tap must be specified as an integer"], JSON_PRETTY_PRINT);
            }
            $timestamp = filter_input(INPUT_POST, 'timestamp', FILTER_VALIDATE_INT);
            if($timestamp === false) {
                print json_encode(['success' => 0, 'message' => "Timestamp must be specified as an integer (UNIX epoch)"], JSON_PRETTY_PRINT);
            }
            if($db->addBeer($cardId, $volume, $tap, $timestamp)) {
                print json_encode(['success' => 1, 'message' => 'Added beer'], JSON_PRETTY_PRINT);
            } else {
                print json_encode(['success' => 0, 'message' => 'Failed adding beer'], JSON_PRETTY_PRINT);
            }
            break;
        case 'updateUser':
            $cardId = filter_input(INPUT_POST, 'cardid', FILTER_SANITIZE_STRING);
            $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
            $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
            if($db->updateUser($cardId, $name, $department)) {
                print json_encode(['success' => 1, 'message' => 'Updated user'], JSON_PRETTY_PRINT);
            } else {
                print json_encode(['success' => 0, 'message' => 'Could not update user'], JSON_PRETTY_PRINT);
            }
            break;
        case '':
            print json_encode(['success' => 0, 'message' => "Action is empty"], JSON_PRETTY_PRINT);
            break;
        default:
            print json_encode(['success' => 0, 'message' => "Unknown action '$action'"], JSON_PRETTY_PRINT);
    }
}
            


