<?php

session_start();

require_once('settings.inc.php');
require_once('db.inc.php');
require_once('election.inc.php');

// Check for a voting code
if (!isset($_POST['votingcode']) && !isset($_SESSION['votingcode'])) {
    header('Location: ' . $BASE_URL . 'index.php?message=Voting+code+required');
    exit();
}

// Load data and check that voting code is valid
load_data();
//print_r($votingcodes);
if (isset($_POST['votingcode'])) {
    $_POST['votingcode'] = strtoupper($_POST['votingcode']);
    
    if (isset($_SESSION['votingcode']) && unserialize($_SESSION['votingcode'])->code != $_POST['votingcode']) { // someone's obviously used the Back button...
        session_unset();
    }
    
    $votingcode = null;
    if (isset($votingcodes[$_POST['votingcode']])) {
        $votingcode = $votingcodes[$_POST['votingcode']];
        if ($votingcode->status != 'UNUSED') {
            header('Location: ' . $BASE_URL . 'index.php?message=Voting+code+invalid');
            exit();
        }
    } else {
        header('Location: ' . $BASE_URL . 'index.php?message=Voting+code+invalid');
        exit();
    }
    // OK, we're valid. Save the voting code in the session
    $_SESSION['votingcode'] = serialize($votingcode);
} else {
    $votingcode = unserialize($_SESSION['votingcode']);
}
//print_r($_SESSION['votingcode']);
// TODO: SET THE VOTING CODE TO IN_USE

?><!DOCTYPE html>
<html>
<head>
<title>CSSA Vote</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
<script src="js/jquery-1.9.1.js"></script>
<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="vote.js"></script>
<style>
li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: 50px; }
.ui-state-highlight {
    height: 1.5em;
    line-height: 1.2em;
    background-color: red;
}
.candidates_unselected_div {
    width: 50%;
    float: left;
}

.candidates_selected_div {
    width: 50%;
    float: right;
}

.election {
    width: 50%;
    margin-left: auto;
    margin-right: auto;
}

.clearfix:after {
   content: " "; /* Older browser do not support empty content */
   visibility: hidden;
   display: block;
   height: 0;
   clear: both;
}

ul {
min-height: 100px;
}
ol {
min-height: 100px;
}
.election_title_hidden { display: none; }
.election_num_pos_hidden { display: none; }
</style>
</head>
<body>
<noscript>
<h1>This application requires JavaScript to be enabled</h1>
</noscript>
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
<h3>Drag the candidates you wish to vote for from the list on the left to the list on the right, with your most preferred candidate at the top and your least preferred candidate at the bottom.</h3>
<form action="vote_cast.php" method="POST" id="voteform">
<input type="hidden" name="vote" id="votestring">
<?php

// Display each election
ksort($votingcode->elections);
foreach ($votingcode->elections as $election) {
?>
<div class="election clearfix" id="e<?php echo $election->election_id; ?>">
<h2><?php echo $election->title; ?></h2>
<h2>You must select at least <?php echo $election->num_positions; ?> candidate<?php if ($election->num_positions > 1) { ?>s<?php } ?></h2>
<div class="election_title_hidden"><?php echo $election->title; ?></div>
<div class="election_num_pos_hidden"><?php echo $election->num_positions; ?></div>
<div class="candidates_unselected_div">
<h3>Unselected Candidates<br>(in random order)</h3>
<ul id="u<?php echo $election->election_id; ?>" class="ui-state-default candidates_unselected">
<?php
    // Shuffle candidates

    $candidates = $election->candidates;
    shuffle($candidates);

    foreach ($candidates as $candidate) {
?>
<li id="c<?php echo $candidate->candidate_id ?>"><?php echo $candidate->name; ?></li>
<?php
}
?>
</ul></div>

<div class="candidates_selected_div"><h3>Selected Candidates<br>(in order of preference)</h3><ol id="s<?php echo $election->election_id; ?>" class="ui-state-default candidates_selected"></ol></div>

</div>
<?php
}

?>

<div style="width: 100%;"><input onClick="submit_vote();" type="button" value="Cast Vote"></div>
</form>

</div>
</body>
</html>