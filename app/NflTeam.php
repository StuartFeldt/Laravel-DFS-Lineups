<?php

namespace App;

use App\Team;
use Illuminate\Database\Eloquent\Model;

class NflTeam extends Team
{
	public $qb;
	public $rb1;
	public $rb2;
	public $wr1;
	public $wr2;
	public $wr3;
	public $te;
	public $k;
	public $d;
	protected $positions = ['qb', 'rb1', 'rb2', 'wr1', 'wr2', 'wr3', 'te', 'k', 'd'];

	public function init($stack = false, $locks = false)
	{
		$this->roster = ['qb' => $this->qb, 'rb1' => $this->rb1, 'rb2' => $this->rb2, 'wr1' => $this->wr1, 'wr2' => $this->wr2, 'wr3' => $this->wr3, 'te' => $this->te, 'k' => $this->k, 'd' => $this->d];

		$this->calcSal();
		$this->calcPts();

		$this->isValid = true;
		
		if($this->salary <= 60000) {
			$this->isValid = true;
		} else {
			$this->isValid = false;
		}

		if($this->rb1->fd_id == $this->rb2->fd_id || 
			$this->wr1->fd_id == $this->wr2->fd_id ||
			$this->wr1->fd_id == $this->wr3->fd_id ||
			$this->wr2->fd_id == $this->wr3->fd_id) {
			$this->isValid = false;
		}

		$teams = [];
		$qb_wr_stacks = [];
		$ids = [];
		$opps = [];
		foreach($this->roster as $pos => $player) {

			$ids[] = $player->player->fd_id;
			if(isset($teams[$player->player->team])) {
				$teams[$player->player->team]++;
			} else {
				$teams[$player->player->team] = 1;
			}

			if($player->player->opp == $this->d->player->team) {
				$this->isValid = false;
			}
		}

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
		
		if($stack) {
			$has_a_stack = false;
			$qb_team = $this->qb->player->team;

			if($stack == 1) {
				if($this->wr1->player->team == $qb_team ||
					$this->wr2->player->team == $qb_team ||
					$this->wr3->player->team == $qb_team
					) {
					$has_a_stack = true;
				}
			} else if($stack == 2)  {
				if(($this->wr1->player->team == $qb_team && $this->wr2->player->team == $qb_team) ||
					($this->wr1->player->team == $qb_team && $this->wr3->player->team == $qb_team) ||
					($this->wr3->player->team == $qb_team && $this->wr2->player->team == $qb_team)
					) {
					$has_a_stack = true;
				}
			}
		
			if(!$has_a_stack) {
				$this->isValid = false;
			} 
		}

		if(sizeof($teams) < 2) {
			$this->isValid = false;
		}

		foreach($teams as $name => $team) {
			if($team > 4) {
				$this->isValid = false;
			}
		}
	}

	public function save($name, $type)
	{
	    $lu = Lineup::firstOrNew(['hash' => $this->getHash(), 'slate' => env('SLATE')]);
	    $lu->name = $name;
	    $lu->salary = $this->salary;
	    $lu->points = $this->points;
	    $lu->projection = $type;
	    $lu->qb = $this->qb->player->fd_id;
	    $lu->rb1 = $this->rb1->player->fd_id;
	    $lu->rb2 = $this->rb2->player->fd_id;
	    $lu->wr1 = $this->wr1->player->fd_id;
	    $lu->wr2 = $this->wr2->player->fd_id;
	    $lu->wr3 = $this->wr3->player->fd_id;
	    $lu->te = $this->te->player->fd_id;
	    $lu->k = $this->k->player->fd_id;
	    $lu->d = $this->d->player->fd_id;
	    $lu->save();
	}

	public function translatePosition($pos)
	{
		if(strpos($pos, 'rb') !== false) {
			return 'rb';
		} else if(strpos($pos, 'wr') !== false) {
			return 'wr';
		} else {
			return $pos;
		}
	}
}