<?php

session_start();

require_once('settings.inc.php');
require_once('db.inc.php');
require_once('election.inc.php');

// Log in if credentials provided
if (isset($_POST['username']) && isset($_POST['password'])) {
    session_unset();
    $query = q('SELECT * FROM ' . $DB_TABLE_PREFIX . 'users WHERE username = ?;');
    $query->bind_param('s', $_POST['username']);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows == 1) {
        $hashed_password = $result->fetch_assoc()['password'];
        if (crypt($_POST['password'], $hashed_password) == $hashed_password) {
            $_SESSION['username'] = $_POST['username'];
        } else {
            header('Location: ' . $BASE_URL . 'login.php?message=Authentication+failed');
            exit();
        }
    } else {
        header('Location: ' . $BASE_URL . 'login.php?message=Authentication+failed');
        exit();
    }
} else {
    // Check if not logged in
    if (!isset($_SESSION['username'])) {
        header('Location: ' . $BASE_URL . 'login.php');
        exit();
    }
}

load_data();

?><!DOCTYPE html>
<html>
<head>
<title>CSSA Vote</title>
<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
<script src="js/jquery-1.9.1.js"></script>
<script src="js/jquery-ui-1.10.3.custom.min.js"></script>
<script src="bootstrap/js/bootstrap.js"></script>
<script src="admin.js"></script>
<style>li { margin: 0 3px 3px 3px; padding: 0.4em; padding-left: 1.5em; font-size: 1.4em; height: 18px; }</style>
</head>
<body>
<div style="text-align:center">
<img src="img/cssa_logo.png">
<h1>CSSA Voting Administrator Interface</h1>

<?php
if (isset($_GET['message'])) {
?>
<div class="alert" style="width:300px;margin:0 auto"><b><?php echo $_GET['message']; ?></b></div>
<?php
}


function new_candidate() {
    global $candidates;
    global $DB_TABLE_PREFIX;
    if (isset($_POST['candidate_name'])) {
        $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'candidates (name) VALUES (?);');
        $query->bind_param('s', $_POST['candidate_name']);
        $result = $query->execute();
        if ($result) echo "<h2>Update Successful!</h2>";
        else echo "<h2>Error!</h2>";
    }
    
    echo '<h1>New Candidate</h1>';
    echo '<form action="admin.php?action=new_candidate" method="POST">';
    echo '<label>Name:</label><input name="candidate_name" type="text" length="30" maxlength="64">';
    echo '<br><input type="submit"></form>';
}

function edit_candidate() {
    global $candidates;
    global $DB_TABLE_PREFIX;
    
    if (!isset($_GET['id'])) {
        echo '<h1>Edit Candidates</h1>';
        echo '<ul>';
        foreach ($candidates as $candidate) {
            echo '<li><a href="admin.php?action=edit_candidate&amp;id=' . $candidate->candidate_id . '">' . $candidate->name . '</a></li>';
        }
        echo '</ul>';
        return;
    }
    
    if (isset($_POST['candidate_name'])) {
        if ($_POST['delete']) {
            $query = q('DELETE FROM ' . $DB_TABLE_PREFIX . 'candidates WHERE candidate_id = ?;');
            $query->bind_param('i', $_GET['id']);
            $result = $query->execute();
            if ($result) echo "<h2>Delete Successful!</h2>";
            else echo "<h2>Error!</h2>";
            echo '<h2><a href="admin.php?action=edit_candidate">Back</a></h2>';
            return;
        }
        
        $candidate = $candidates[$_GET['id']];
        $candidate->name = $_POST['candidate_name'];
        $query = q('UPDATE ' . $DB_TABLE_PREFIX . 'candidates SET name = ? WHERE candidate_id = ?;');
        $query->bind_param('si', $_POST['candidate_name'], $_GET['id']);
        $result = $query->execute();
        if ($result) echo "<h2>Update Successful!</h2>";
        else echo "<h2>Error!</h2>";
    }
    
    // Retrieve candidate
    $candidate = $candidates[$_GET['id']];
    echo '<h1>Edit Candidate - ' . $candidate->name . '</h1>';
    echo '<form action="admin.php?action=edit_candidate&amp;id=' . $candidate->candidate_id . '" method="POST">';
    echo '<label>Name:</label><input name="candidate_name" type="text" length="30" maxlength="64" value="' . $candidate->name . '">';
    echo '<br><input type="submit" name="update" value="Update"> <input type="submit" name="delete" value="Delete"></form>';
    
    echo '<h2><a href="admin.php?action=edit_candidate">Back</a></h2>';

}


function new_election() {
    global $elections, $candidates, $mysqli;
    global $DB_TABLE_PREFIX;
    
    if (isset($_POST['create_election'])) {
        $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'elections (title, num_positions, created) VALUES (?, ?, NOW());');
        $query->bind_param('si', $_POST['election_title'], $_POST['num_positions']);
        $result = $query->execute();
        $election_id = $mysqli->insert_id;
        if ($result) {
            echo "<h2>Insertion Successful!</h2>";
            foreach ($_POST['candidates'] as $candidate_id) {
                $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'elections_candidates (election_id, candidate_id) VALUES (?, ?);');
                $query->bind_param('ii', $election_id, $candidate_id);
                $result = $query->execute();
                if ($result) { echo "<h2>Candidate Addition Successful!</h2>"; }
                else { echo "<h2>Error adding candidate!</h2>"; }
            }
        } else { echo "<h2>Error!</h2>"; echo $mysqli->error; }
        return;
    }
    
    echo '<h1>Create New Election</h1>';
    echo '<form action="admin.php?action=new_election" method="POST">';
    echo '<label>Election:</label><input name="election_title" type="text" length="30" maxlength="64">';
    echo '<br><br><label>Num Positions:</label><input name="num_positions" type="text" length="5" maxlength="5">';
    echo '<br><br><table style="margin-left:auto;margin-right:auto">';
    echo '<tr><th>Candidate Name</th><th>Add?</th></tr>';
    foreach ($candidates as $candidate) {
        echo '<tr><td>' . $candidate->name . '</td><td><input type="checkbox" name="candidates[]" value="' . $candidate->candidate_id . '"></td></tr>';
    }
    echo '</table>';
    echo '<br><br><input type="submit" name="create_election" value="Create Election"></form>';
   
}


function edit_election() {
    global $elections, $candidates, $mysqli;
    global $DB_TABLE_PREFIX;
    
    if (!isset($_GET['id'])) {
        echo '<h1>Edit Elections</h1>';
        echo '<ul>';
        foreach ($elections as $election) {
            echo '<li><a href="admin.php?action=edit_election&amp;id=' . $election->election_id . '">' . $election->title . '</a></li>';
        }
        echo '</ul>';
        return;
    }
    
    if (isset($_POST['election_title'])) {
        if (isset($_POST['delete'])) {
            // TODO: THIS DOESN'T DELETE EVERYTHING ASSOCIATED WITH IT
            $query = q('DELETE FROM ' . $DB_TABLE_PREFIX . 'elections WHERE election_id = ?;');
            $query->bind_param('i', $_GET['id']);
            $result = $query->execute();
            if ($result) echo "<h2>Delete Successful!</h2>";
            else { echo "<h2>Error!</h2>"; echo $mysqli->error; }
            echo '<h2><a href="admin.php?action=edit_election">Back</a></h2>';
            return;
        }
        
        $election = $elections[$_GET['id']];
        
        if (isset($_POST['delete_candidates'])) {
            foreach ($_POST['delete_candidates'] as $candidate_id) {
                $query = q('DELETE FROM ' . $DB_TABLE_PREFIX . 'elections_candidates WHERE election_id = ? AND candidate_id = ?;');
                $query->bind_param('ii', $election->election_id, $candidate_id);
                $result = $query->execute();
                if ($result) {
                    echo "<h2>Delete Candidates Successful!</h2>";
                    // TODO: INEFFICIENT
                    load_data();
                }
                else echo "<h2>Error!</h2>";
            }
        }
        

        $election->title = $_POST['election_title'];
        $election->num_positions = $_POST['num_positions'];
        $query = q('UPDATE ' . $DB_TABLE_PREFIX . 'elections SET title = ?, num_positions = ? WHERE election_id = ?;');
        $query->bind_param('sii', $_POST['election_title'], $_POST['num_positions'], $_GET['id']);
        $result = $query->execute();
        if ($result) echo "<h2>Update Successful!</h2>";
        else echo "<h2>Error!</h2>";
        
        if ($_POST['add_more_candidates']) {
            echo '<form action="admin.php?action=edit_election&amp;id=' . $election->election_id . '" method="POST">';
            echo '<table style="margin-left:auto;margin-right:auto">';
            echo '<tr><th>Candidate Name</th><th>Add?</th></tr>';
            foreach ($candidates as $candidate) {
                echo '<tr><td><input type="checkbox" name="add_candidates[]" value="' . $candidate->candidate_id . '"></td><td>' . $candidate->name . '</input></td></tr>';
            }
            echo '</table>';
            echo '<br><br><input name="add_candidates_submit" type="submit" value="Add Candidates">';
            echo '</form>';
            return;
        }
        
    }
    
    if (isset($_POST['add_candidates_submit'])) {
        foreach ($_POST['add_candidates'] as $candidate_id) {
            $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'elections_candidates (election_id, candidate_id) VALUES (?,?);');
            $query->bind_param('ii', $_GET['id'], $candidate_id);
            $result = $query->execute();
            if ($result) echo "<h2>Update Successful!</h2>";
            else { echo "<h2>Error!</h2>"; echo $mysqli->error; }
        }
        load_data();
    }
    
    // Retrieve election
    $election = $elections[$_GET['id']];
    echo '<h1>Edit Election - ' . $election->title . '</h1>';
    echo '<form action="admin.php?action=edit_election&amp;id=' . $election->election_id . '" method="POST">';
    echo '<label>Election:</label><input name="election_title" type="text" length="30" maxlength="64" value="' . $election->title . '">';
    echo '<br><br><label>Num Positions:</label><input name="num_positions" type="text" length="5" maxlength="5" value="' . $election->num_positions . '">';
    echo '<br><br><table style="margin-left:auto;margin-right:auto">';
    echo '<tr><th>Candidate Name</th><th>Delete?</th></tr>';
    foreach ($election->candidates as $candidate) {
        echo '<tr><td>' . $candidate->name . '</td><td><input type="checkbox" name="delete_candidates[]" value="' . $candidate->candidate_id . '"></td></tr>';
    }
    echo '</table>';
    echo '<br><br><input type="submit" name="add_more_candidates" value="Add More Candidates">';
    echo '<br><br><input type="submit" name="update" value="Update"> <input type="submit" name="delete" value="Delete"></form>';
    
    echo '<h2><a href="admin.php?action=edit_election">Back</a></h2>';

}


function export_election() {
    global $elections, $votes, $candidates;
    global $DB_TABLE_PREFIX;
    
    // Step 1 - select election
    
    if (!isset($_GET['id'])) {
        echo '<h1>Export Elections</h1>';
        echo '<ul>';
        foreach ($elections as $election) {
            echo '<li><a href="admin.php?action=export_election&amp;id=' . $election->election_id . '">' . $election->title . '</a></li>';
        }
        echo '</ul>';
        return;
    }
    
    $election = $elections[$_GET['id']];
    
    // Step 2 - select exclusions
    
    if (!isset($_POST['export'])) {
        echo '<h1>Export Election - ' . $election->title . '</h1>';
        echo '<form action="admin.php?action=export_election&amp;id=' . $_GET['id'] . '" method="POST"><table style="margin-left:auto;margin-right:auto;">';
        echo '<tr><th>Candidate Name</th><th>Exclude?</th></tr>';
        foreach ($election->candidates as $candidate) {
            echo '<tr><td>' . $candidate->name . '</td><td><input name="exclusions[]" type="checkbox" value="' . $candidate->candidate_id . '"></td></tr>';
        }
        echo '</table><br><br><input type="submit" name="export" value="Export"></form>';
        return;
    }
    
    // Step 3 - export
    
    // Line 1 - '#candidates #positions'
    $num_candidates = count($election->candidates);
    $num_positions = $election->num_positions;
    $blt = $num_candidates . ' ' . $num_positions . "\n";
    
    // Line 2 - exclusions
    if (isset($_POST['exclusions'])) {
        foreach ($_POST['exclusions'] as $exclusion) {
            $blt = $blt . '-' . $exclusion . ' ';
        }
        $blt = $blt . "\n";
    }
    
    // Line 3-m - the votes!
    $election_candidates = array();
    $num_candidates_indexed = 0; // will be incremented to 1 by first candidate encountered
    foreach ($votes as $vote) {
        if ($vote->election_id == $election->election_id && $vote->is_formal()) {
            $blt .= '1';
            foreach ($vote->preferences as $preference) {
                // Translate from candidate IDs to the order of candidates we'll output in the BLT
                if (!isset($election_candidates[$preference])) {
                    $num_candidates_indexed++;
                    $election_candidates[$preference] = $num_candidates_indexed;
                }
                $blt .= ' ' . $election_candidates[$preference];
            }
            $blt .= " 0\n";
        }
    }
    
    // Line m+1 - Last vote, now to output the candidates
    $blt .= "0\n";
    
    // Lines m+2 - n - Candidate names
    // output the candidates that actually did appear in ballots
    for ($i = 1; $i <= $num_candidates; $i++) {
        if (array_search($i, $election_candidates) != FALSE) {
            $blt .= "\"" . $candidates[array_search($i, $election_candidates)]->name . "\"\n";
        }
    }
    
    // now output the candidates that didn't appear
    foreach ($election->candidates as $election_candidate) {
        if (!isset($election_candidates[$election_candidate->candidate_id])) {
            $blt .= "\"" . $election_candidate->name . "\"\n";
        }
    }
    
    // Line n+1 - Title
    $blt .= "\"" . $election->title . "\"";
    
    echo '<pre style="text-align:left">';
    echo $blt;
    echo '</pre>';
    
}

function new_votingcodes() {
    global $votingcodes, $elections;
    global $DB_TABLE_PREFIX;
    if (isset($_POST['codes'])) {
        $codes = explode("\n", $_POST['codes']);
        foreach ($codes as $code) {
            $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'votingcodes (code, status, created) VALUES (?, "UNUSED", NOW());');
            $query->bind_param('s', $code);
            $result = $query->execute();
            if ($result) {
                print_r($_POST['elections']);
                echo "<h2>Update Successful!</h2>";
                // FIXME: This doesn't work as expected. Maybe a bug in the frontend
                foreach ($_POST['elections'] as $election_id => $election_order) {
                    if ($election_id !== FALSE and $election_order !== NULL) {
                        $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'votingcodes_elections (code, election_id, election_order) VALUES (?, ?, ?);');
                        $query->bind_param('sii', $code, $election_id, $election_order);
                        $result = $query->execute();
                        if ($result) echo "<h2>Update Successful!</h2>";
                        else echo "<h2>Error!</h2>";
                    }
                }
            
            } else echo "<h2>Error!</h2>";
        }
        return;
    }
    
    echo '<h1>New Voting Codes</h1>';
    echo '<form action="admin.php?action=new_votingcodes" method="POST">';
    echo '<label>Codes:</label><textarea name="codes" rows="10" cols="10"></textarea><br><br>';
    
    echo '<h3>Give the order that you wish the elections to appear on the voting screen (1 being the top)</h3>';
    
    echo '<ul>';
    foreach ($elections as $election) {
        echo '<li><input type="text" name="elections[' . $election->election_id . ']">' . $election->title . '</li>';
    }
    echo '</ul>';
        
        
    echo '<br><input type="submit"></form>';
}


function edit_votingcodes() {

}

function main_menu() {
    echo '<h2><a href="admin.php?action=new_candidate">Create New Candidate</a></h2>';
    echo '<h2><a href="admin.php?action=edit_candidate">Edit Candidates</a></h2>';
    echo '<h2><a href="admin.php?action=new_election">Create New Election</a></h2>';
    echo '<h2><a href="admin.php?action=edit_election">Edit Elections</a></h2>';
    echo '<h2><a href="admin.php?action=export_election">Export Election</a></h2>';
    echo '<h2><a href="admin.php?action=new_votingcodes">Create New Voting Codes</a></h2>';
    echo '<h2><a href="admin.php?action=edit_votingcodes">Edit Voting Codes</a></h2>';
    echo '<h2><a href="admin.php?action=logout">Logout</a></h2>';
}


function logout() {
    session_destroy();
    echo '<h2>Logged out!</h2>';
    return;
}

if (!isset($_GET['action'])) { main_menu(); }
else {
    if ($_GET['action'] === 'new_candidate') {
        new_candidate();
    } elseif ($_GET['action'] === 'edit_candidate') {
        edit_candidate();
    } elseif ($_GET['action'] === 'new_election') {
        new_election();
    } elseif ($_GET['action'] === 'edit_election') {
        edit_election();
    } elseif ($_GET['action'] === 'export_election') {
        export_election();
    } elseif ($_GET['action'] === 'new_votingcodes') {
        new_votingcodes();
    } elseif ($_GET['action'] === 'edit_votingcodes') {
        edit_votingcodes();
    } elseif ($_GET['action'] === 'logout') {
        logout();
    } else {
        main_menu();
    }
}
?>
<h2><a href="admin.php">Home</a></h2>
</div>
</body>
</html>