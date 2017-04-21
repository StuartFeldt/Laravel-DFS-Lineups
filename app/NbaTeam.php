<?php

namespace App;

use App\Team;
use Illuminate\Database\Eloquent\Model;

class NbaTeam extends Team
{
	public $pg1;
	public $pg2;
	public $sg1;
	public $sg2;
	public $sf1;
	public $sf2;
	public $pf1;
	public $pf2;
	public $c;
	protected $positions = ['pg1', 'pg2', 'sg1', 'sg2', 'sf1', 'sf2', 'pf1', 'pf2', 'c'];

	public function init($stack = false, $locks = false)
	{
		$this->roster = ['pg1' => $this->pg1, 'pg2' => $this->pg2, 'sg1' => $this->sg1, 'sg2' => $this->sg2, 'sf1' => $this->sf1, 'sf2' => $this->sf2, 'pf1' => $this->pf1, 'pf2' => $this->pf2, 'c' => $this->c];

		$this->calcSal();
		$this->calcPts();

		$this->isValid = true;
		
		// Check salary
		if($this->salary <= 60000) {
			$this->isValid = true;
		} else {
			$this->isValid = false;
		}

		// Check for dupe players
		if($this->pg1->fd_id == $this->pg2->fd_id || 
			$this->pf1->fd_id == $this->pf2->fd_id ||
			$this->sg1->fd_id == $this->sg2->fd_id ||
			$this->sf1->fd_id == $this->sf2->fd_id) {
			$this->isValid = false;
		}

		// Check for max team stacks and # teams rostered
		$teams = [];
		$ids = [];
		$opps = [];
		foreach($this->roster as $pos => $player) {

			$ids[] = $player->player->fd_id;
			if(isset($teams[$player->player->team])) {
				$teams[$player->player->team]++;
			} else {
				$teams[$player->player->team] = 1;
			}
		}

		if(sizeof($teams) < 3) {
			$this->isValid = false;
		}

		foreach($teams as $name => $team) {
			if($team > 4) {
				$this->isValid = false;
			}
		}

		// Ensure locks are present if we have any
		if($locks) {
			$has_all_locks = true;
			foreach($locks as $lock) {
				if(!in_array($lock, $ids)) {
					$has_all_locks = false;
				}
			}
			if(!$has_all_locks) {
				$this->isValid = false;
			}
		}
	}

	public function save($name, $type)
    {
        $lu = NbaLineup::firstOrNew(['hash' => $this->getHash(), 'slate' => env('SLATE')]);
        $lu->name = $name;
        $lu->salary = $this->salary;
        $lu->points = $this->points;
        $lu->projection = $type;
        $lu->pg1 = $this->pg1->player->fd_id;
        $lu->pg2 = $this->pg2->player->fd_id;
        $lu->sg1 = $this->sg1->player->fd_id;
        $lu->sg2 = $this->sg2->player->fd_id;
        $lu->sf1 = $this->sf1->player->fd_id;
        $lu->sf2 = $this->sf2->player->fd_id;
        $lu->pf1 = $this->pf1->player->fd_id;
        $lu->pf2 = $this->pf2->player->fd_id;
        $lu->c = $this->c->player->fd_id;
        $lu->save();
    }

    public function translatePosition($pos)
	{
		if(strpos($pos, 'pg') !== false) {
			return 'pg';
		} else if(strpos($pos, 'sg') !== false) {
			return 'sg';
		} else if(strpos($pos, 'sf') !== false) {
			return 'sf';
		} else if(strpos($pos, 'pf') !== false) {
			return 'pf';
		} else {
			return 'c';
		}
	}
}