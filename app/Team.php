<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Team
{

	public $pointsStore;
	public $isValid;
	public $mother;
	public $father;
	public $points;
	public $salary;
	public $roster;
	public $hash;

	public function calcSal()
	{
		$this->salary = 0;
		foreach($this->roster as $pos => $player) {
			$this->salary += intval($player->player->salary);
		}
	}

	public function calcPts()
	{
		$this->points = 0;
		foreach($this->roster as $pos => $player) {
			$this->points += $player->pts;
			$this->pointsStore[] = $player->fd_id;
		}
	}

	public function getHash()
	{
		// \Log::info($this->salary . "-" . $this->points);
		if(isset($this->hash)) {
			return $this->hash;
		}

		sort($this->pointsStore);
		return md5(json_encode($this->pointsStore));
		return $this->salary . "-" . $this->points;
	}

	public function getPositions()
	{
		return $this->positions;
	}

	public function print()
    {
        $headers = ['Pos', 'Name', 'Sal', 'Team', 'Game', 'Pts'];
        $data = [];
        
        foreach($this->roster as $pos => $player) {
            $data[] = [
                $pos, $player->player->name, $player->player->salary, $player->player->team, $player->player->game, $player->pts
            ];
        }
        $data[] = ['', '', $this->salary, '','',$this->points];
        return ['headers' => $headers, 'data' => $data];
    }

}