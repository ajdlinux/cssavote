<?php

session_start();

require_once('settings.inc.php');
require_once('db.inc.php');
require_once('election.inc.php');

load_data();

// Check for a voting code and a vote string
if (!isset($_SESSION['votingcode']) || !isset($_POST['vote'])) {
    header('Location: ' . $BASE_URL . 'index.php?message=Vote+invalid');
    exit();
}

$votingcode = unserialize($_SESSION['votingcode']);

// Check voting code still valid
if ($votingcode->status == 'USED') {
    header('Location: ' . $BASE_URL . 'index.php?message=You\'ve+already+voted');
    exit();
}

$vote_array = json_decode($_POST['vote'], true);

// Validate vote array
foreach ($vote_array as $election_id => $election_array) {
    $found = false;
    foreach ($votingcode->elections as $votingcode_election) {
        if ($votingcode_election->election_id === $election_id) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        header('Location: ' . $BASE_URL . 'index.php?message=Vote+invalid');
        exit();
    }
    
    $election = $elections[$election_id];
    
    foreach ($election_array as $preference => $candidate_id) {
        $found = false;
        foreach ($election->candidates as $candidate) {
            if ($candidate->candidate_id === $candidate_id) {
                $found = true;
                break;
            }
        }
        
        if (!$found) { // TODO: Check that preference is valid as well
            header('Location: ' . $BASE_URL . 'index.php?message=Vote+invalid');
            exit();
        }
    }
}

// Vote!
foreach ($vote_array as $election_id => $election_array) {
    $vote = new Vote(null, $election_id);
    $pref = 1;
    foreach ($election_array as $candidate_id) {
        $vote->add_preference($candidate_id, $pref);
        $pref++;
    }
    $vote->cast();
}

// FIXME FIXME FIXME Set voting code to used
$query = q('UPDATE ' . $DB_TABLE_PREFIX . 'votingcodes SET status="USED", used=NOW() WHERE code=?;');
$query->bind_param('s', $votingcode->code);
$result = $query->execute();
if (!$result) echo "<h1>ERROR ERROR ERROR</h1>";

// End the session!
session_destroy();

?><!DOCTYPE html>
<html>
<head>
<title>CSSA Vote</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
<script src="bootstrap/js/bootstrap.js"></script>
</head>
<body>
<div style="text-align:center">
<img src="img/cssa_logo.png">
<h1>CSSA Voting</h1>

<?php
if (isset($_GET['message'])) {
?>
<div class="alert" style="width:300px;margin:0 auto"><b><?php echo $_GET['message']; ?></b></div>
<?php
}
?>

<h3>Thank you for voting!</h3>
<form action="index.php" method="POST">
<input type="submit" value="Return to Homepage">
</form>
</div>
</body>
</html>