<?php

require_once('settings.inc.php');

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASSWORD, $DB_NAME, $DB_PORT);

if ($mysqli->connect_errno) {
    printf("Connect failed: %s\n", $mysqli->connect_error);
    exit();
}

// Prepare a query and die if there's an error
//
// Input:
//    $query - the query string
//
// Returns: statement handle
function q($query) {
    global $mysqli;
    $q_prepared = $mysqli->prepare($query);
    if ($q_prepared === FALSE) { die($mysqli->error); }
    return $q_prepared;
}