<?php
error_reporting(E_ALL);
require_once('settings.inc.php');
require_once('db.inc.php');
require_once('election.inc.php');
header('Content-Type: text/plain');
echo "TRYING TO LOAD DATA\n";

load_data();

echo "CANDIDATES\n";
echo "----------\n";
echo "\n";

print_r($candidates);