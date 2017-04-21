<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class Exclude extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:exclude {--id=false} {--team=false} {--game=false} {--pos=false} {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge proj';

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
        $id = $this->option('id');
        $team = $this->option('team');
        $game = $this->option('game');
        $reset = $this->option('reset');
        $pos = $this->option('pos');
        $slate = env('SLATE');
        
        if($id) {
            if($reset) {
                Player::withTrashed()->where('fd_id', $id)->restore();
            } else {
                Player::where('fd_id', $id)->delete();
            }
        }

        if($team) {
            if($reset) {
                Player::withTrashed()->where('team', $team)->restore();
            } else {
                Player::where('team', $team)->delete();
            }
        }

        if($game) {
            if($reset) {
                Player::withTrashed()->where('game', $game)->restore();
            } else {
                Player::where('game', $game)->delete();
            }
        }

        if($pos) {
            if($reset) {
                Player::withTrashed()->where('pos', $pos)->restore();
            } else {
                Player::where('pos', $pos)->delete();
            }
        }

        if(!$game && !$team && !$id && $reset) {
            Player::withTrashed()->where('slate', $slate)->restore();
        }
    }
}
