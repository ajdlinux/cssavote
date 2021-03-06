<?php

require_once('settings.inc.php');
require_once('db.inc.php');

class Candidate {
    // Create a new Candidate instance.
    //
    // Input:
    //     $candidate_id - candidate's database ID (integer)
    //     $name - candidate's name (string)
    //
    // Returns: new Candidate instance
    function __construct($candidate_id, $name) {
        $this->candidate_id = $candidate_id;
        $this->name = $name;
    }
}

class Election {
    // Create a new Election instance.
    //
    // Input:
    //     $election_id - election's database ID (integer)
    //     $title - title of the election (string)
    //     $num_positions - number of positions to be elected (integer)
    //     $created - time of election creation (datetime)
    //     $start_time - time of start of polling (datetime)
    //     $end_time - time of end of polling (datetime)
    //
    // Returns: new Election instance
    function __construct($election_id, $title, $num_positions, $created, $start_time, $end_time) {
        $this->election_id = $election_id;
        $this->title = $title;
        $this->num_positions = $num_positions;
        $this->created = $created;
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->candidates = array();
    }
    
    // Add a new candidate.
    //
    // Input:
    //     $candidate - new candidate (Candidate)
    //
    // Returns: nothing
    //
    function add_candidate($candidate) {
        $this->candidates[] = $candidate;
    }
    
    // Delete a candidate.
    //
    // Input:
    //     $candidate_id - ID of candidate
    //
    // Returns: nothing
    function delete_candidate($candidate_id) {
        foreach ($this->candidates as $index => $candidate) {
            if ($candidate->candidate_id == $candidate_id) {
                unset($this->candidates[$index]);
            }
        }
    }
}

class VotingCode {
    // Create a new VotingCode instance.
    //
    // Input:
    //    $code - the voting code (string)
    //    $status - voting code status (enum 'UNUSED', 'IN_USE', 'USED')
    //    $created - time of creation (datetime)
    //    $used - time that the voting code is used (datetime)
    //
    // Returns: new VotingCode instance
    function __construct($code, $status, $created, $used) {
        $this->code = $code;
        $this->status = $status;
        $this->created = $created;
        $this->used = $used;
        $this->elections = array();
    }
    
    // Add a new election.
    //
    // Input:
    //     $election - new election (Election)
    //
    // Returns: nothing
    //
    function add_election($election, $order) {
        $this->elections[$order] = $election;
    }
    
    // Delete an election.
    //
    // Input:
    //     $election_id - ID of election
    //
    // Returns: nothing
    function delete_election($election_id) {
        foreach ($this->elections as $index => $election) {
            if ($election->election_id == $election_id) {
                unset($this->elections[$index]);
            }
        }
    }
}


class Vote {
    // Create a new Vote instance.
    //
    // Input:
    //    $vote_id - the vote ID (integer) - THIS IS NOT THE VOTING CODE
    //    $election_id - the election ID (integer)
    //
    // Returns: new Vote instance
    function __construct($vote_id, $election_id) {
        $this->vote_id = $vote_id;
        $this->election_id = $election_id;
        $this->preferences = array();
    }
    
    // Add a preference.
    //
    // Input:
    //    $candidate_id - the candidate ID (integer)
    //    $preference - the preference number (integer)
    //    
    // Returns: true if preference added successfully, false otherwise // TODO: should this be changed to an exception?
    function add_preference($candidate_id, $preference) {
        // TODO: check that 0 < $preference <= $num_positions
        // TODO: check that we're not overwriting an existing preference
        $this->preferences[$preference] = $candidate_id;
        return true;
    }
    
    // Remove a preference.
    //
    // Input:
    //    $preference - the preference number (integer)
    // TODO: implement remove_preference
    
    // Check formality.
    //
    // Returns: whether vote is formal (boolean)
    function is_formal() {
        global $elections;
        return count($this->preferences) >= $elections[$this->election_id]->num_positions;
    }
    
    // Cast the vote
    //
    //
    function cast() {
        global $mysqli, $DB_TABLE_PREFIX;
        $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'votes (election_id) VALUES (?)');
        $query->bind_param('i', $this->election_id);
        $query->execute();
        // if (!$query->get_result()) { die($mysqli->error); }
        $this->vote_id = $mysqli->insert_id;
        
        foreach ($this->preferences as $preference => $candidate_id) {
            $query = q('INSERT INTO ' . $DB_TABLE_PREFIX . 'votes_preferences (vote_id, preference, candidate_id) VALUES (?, ?, ?)');
            $query->bind_param('iii', $this->vote_id, $preference, $candidate_id);
            $query->execute();
        }
    }
}


// Load everything from the database.
//
// Returns: nothing
function load_data() {
    global $mysqli, $DB_TABLE_PREFIX;
    global $candidates, $elections, $votingcodes, $votes;
    
    // Load candidates
    $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "candidates;");
    $query->execute();
    $result = $query->get_result();
    
    $candidates = array();
    
    foreach ($result as $row) {
        $candidate = new Candidate($row['candidate_id'], $row['name']);
        $candidates[$candidate->candidate_id] = $candidate;
    }
    
    
    // Load elections
    $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "elections;");
    $query->execute();
    $result = $query->get_result();
    
    $elections = array();
    
    foreach ($result as $row) {
        $election = new Election($row['election_id'], $row['title'], $row['num_positions'], $row['created'], $row['start_time'], $row['end_time']);
        $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "elections_candidates WHERE election_id = ?;");
        $query->bind_param('i', $election->election_id);
        $query->execute();
        $result_candidates = $query->get_result();
        foreach ($result_candidates as $row_result_candidates) {
            try {
                $election->add_candidate($candidates[$row_result_candidates['candidate_id']]);
            } catch (Exception $e) {
                die('Error adding Candidate to Election');
            }
        }
        $elections[$election->election_id] = $election;
    }
    
    
    // Load voting codes
    $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "votingcodes;");
    $query->execute();
    $result = $query->get_result();
    
    $votingcodes = array();
    
    foreach ($result as $row) {
        $votingcode = new VotingCode($row['code'], $row['status'], $row['created'], $row['used']);
        $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "votingcodes_elections WHERE code = ?;");
        $query->bind_param('s', $votingcode->code);
        $query->execute();
        $result_elections = $query->get_result();
        
        foreach ($result_elections as $row_result_elections) {
            try {
                $votingcode->add_election($elections[$row_result_elections['election_id']], $row_result_elections['election_order']);
            } catch (Exception $e) {
                die('Error adding Election to VotingCode');
            }
        }
        $votingcodes[$votingcode->code] = $votingcode;
    }
    
    
    // Load votes
    $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "votes;");
    $query->execute();
    $result = $query->get_result();
    
    $votes = array();
    
    foreach ($result as $row) {
        $vote = new Vote($row['vote_id'], $row['election_id']);
        $query = q("SELECT * FROM " . $DB_TABLE_PREFIX . "votes_preferences WHERE vote_id = ?;");
        $query->bind_param('i', $vote->vote_id);
        $query->execute();
        $result_preferences = $query->get_result();
        
        foreach ($result_preferences as $preference) {
            try {
                $vote->add_preference($preference['candidate_id'], $preference['preference']);
            } catch (Exception $e) {
                die('Error adding preference to Vote');
            }
        }
        $votes[$row['vote_id']] = $vote;
    }
}
