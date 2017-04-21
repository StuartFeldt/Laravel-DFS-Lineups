<?php

namespace App\Console\Commands;

use App\Lineup;
use App\Player;
use App\NbaLineup;
use App\NhlLineup;
use App\Projection;
use Illuminate\Console\Command;

class UpdateLuPts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:update_lu_pts {type} {--actuals} {--sport=nfl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update lineups points';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $type = $this->argument('type');
        $actuals = $this->option('actuals');
        $sport = $this->option('sport');
        $projections = Projection::where('type', $type)->get()->keyBy('fd_id');

        if($sport == 'nfl') {
            $lineups = Lineup::where('slate', env('SLATE'))->get();
            $positions = ['qb', 'rb1', 'rb2', 'wr1', 'wr2', 'wr3', 'te', 'k', 'd'];
        } else if($sport == 'nba') {
            $lineups = NbaLineup::where('slate', env('SLATE'))->get();
            $positions = ['pg1', 'pg2', 'sg1', 'sg2', 'sf1', 'sf2', 'pf1', 'pf2', 'c'];
        } else {
            $lineups = NhlLineup::where('slate', env('SLATE'))->get();
            $positions = ['c1', 'c2', 'w1', 'w2', 'w3', 'w4', 'd1', 'd2', 'g'];
        }

        $this->output->progressStart(sizeof($lineups));
        foreach($lineups as $lineup) {
            $total_pts = 0;
            $player_not_found = false;
            foreach($positions as $pos) {
                if(!isset($projections[$lineup->$pos])) {
                    $player_not_found = true;
                    continue;
                }
                $total_pts += $projections[$lineup->$pos]->pts;
            }
            if($player_not_found) {
                continue;
            }

            if($actuals) {
                $lineup->actual_pts = $total_pts;
            } else {
                $lineup->points = $total_pts; 
            }
            $lineup->save();
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();

    }
}
