<?php

namespace App;

class TeamHelper
{
	public $sport;
	public $positions;

	public function __construct($sport)
    {
       	$this->sport = $sport;
       	$this->temp_team = $this->newTeam();
    }

    public function getPositions()
    {
    	return $this->temp_team->getPositions();
    }

    public function newTeam()
    {
    	if($this->sport == 'nfl') {
    		return new NflTeam();
    	} else if($this->sport == 'nhl') {
    		return new NhlTeam();
    	} else if($this->sport == 'nba') {
    		return new NbaTeam();
    	}
    }

    public function countPositions($players)
    {
    	$position_counts = [];
    	foreach($players as $position => $player) {
    		$position_counts[$position] = count($players[$position]) -1;
    	}
    	return $position_counts;
    }

    public function translatePos($pos)
    {
    	return $this->temp_team->translatePosition($pos);
    }

    public function createTeam($players, $position_counts, $stack, $locks)
    {
    	$team = $this->newTeam();
    	$positions_needed = $team->getPositions();
    	foreach($positions_needed as $position) {
    		$pos = $this->translatePos($position);
    		$team->$position = $players[$pos][rand(0, $position_counts[$pos])];
    	}
    	$team->init($stack, $locks);
    	return $team;
    }
}