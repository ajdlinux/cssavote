<?php
error_reporting(E_ALL);
require_once('settings.inc.php');
require_once('db.inc.php');
require_once('election.inc.php');
header('Content-Type: text/plain');
echo "TRYING TO LOAD DATA\n\n";

load_data();

echo "CANDIDATES\n";
echo "----------\n";
echo "\n";

print_r($candidates);

echo "\n\n\n";


echo "ELECTIONS\n";
echo "---------\n";
echo "\n";

print_r($elections);

echo "\n\n\n";


echo "VOTING CODES\n";
echo "------------\n";
echo "\n";

print_r($votingcodes);

echo "\n\n\n";