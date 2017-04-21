<?php

namespace App;

use App\Team;
use Illuminate\Database\Eloquent\Model;

class NhlTeam extends Team
{

	public $c1;
	public $c2;
	public $w1;
	public $w2;
	public $w3;
	public $w4;
	public $d1;
	public $d2;
	public $g;
	protected $positions = ['c1', 'c2', 'w1', 'w2', 'w3', 'w4', 'd1', 'd2', 'g'];

	public function init($stack = false, $locks = false)
	{
		$this->roster = [
			'c1' => $this->c1, 
			'c2' => $this->c2, 
			'w1' => $this->w1, 
			'w2' => $this->w2, 
			'w3' => $this->w3, 
			'w4' => $this->w4, 
			'd1' => $this->d1, 
			'd2' => $this->d2, 
			'g' => $this->g
		];

		$this->calcSal();
		$this->calcPts();

		$this->isValid = true;
		
		if($this->salary <= 55000) {
			$this->isValid = true;
		} else {
			$this->isValid = false;
		}

		if($this->c1->fd_id == $this->c2->fd_id || 
			$this->d1->fd_id == $this->d2->fd_id ||
			$this->w1->fd_id == $this->w2->fd_id ||
			$this->w1->fd_id == $this->w3->fd_id ||
			$this->w1->fd_id == $this->w4->fd_id ||
			$this->w2->fd_id == $this->w3->fd_id ||
			$this->w2->fd_id == $this->w4->fd_id ||
			$this->w3->fd_id == $this->w4->fd_id) {
			$this->isValid = false;
		}

		// check team size, no players v. goalie
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

			if($player->player->opp == $this->g->player->team) {
				$this->isValid = false;
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

		// Ensure locks are present 
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
        $lu = NhlLineup::firstOrNew(['hash' => $this->getHash(), 'slate' => env('SLATE')]);
        $lu->name = $name;
        $lu->salary = $this->salary;
        $lu->points = $this->points;
        $lu->projection = $type;
        $lu->c1 = $this->c1->player->fd_id;
        $lu->c2 = $this->c2->player->fd_id;
        $lu->w1 = $this->w1->player->fd_id;
        $lu->w2 = $this->w2->player->fd_id;
        $lu->w3 = $this->w3->player->fd_id;
        $lu->w4 = $this->w4->player->fd_id;
        $lu->d1 = $this->d1->player->fd_id;
        $lu->d2 = $this->d2->player->fd_id;
        $lu->g = $this->g->player->fd_id;
        $lu->save();
    }

    public function translatePosition($pos)
	{
		if(strpos($pos, 'c') !== false) {
			return 'c';
		} else if(strpos($pos, 'w') !== false) {
			return 'w';
		} else if(strpos($pos, 'd') !== false) {
			return 'd';
		} else {
			return $pos;
		}
	}
}