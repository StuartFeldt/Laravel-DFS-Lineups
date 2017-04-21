<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class MergeProjections extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:proj:merge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merge projections';

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
        $slate = env('SLATE');
        $type = 'all_'.$slate;
        $players = [];

        $projections = Projection::join('fd_players', 'fd_players.fd_id', '=', 'projections.fd_id')->where('slate', $slate)->where('type', '!=', $type)->where('type', '!=', 'actuals_'. $slate)->get()->toArray();

        foreach($projections as $projection) {
            if(isset($players[$projection['fd_id']])) {
                $players[$projection['fd_id']][] = $projection['pts'];
            } else {
                $players[$projection['fd_id']] = [$projection['pts']];
            }
        }
        

        foreach($players as $fd_id => $player) {
            $proj = Projection::firstOrNew(['fd_id' => $fd_id, 'type' => $type]);
            $proj->type = $type;
            $total_proj = 0;
            $num_proj = 0;
            foreach($player as $projection) {
                $num_proj++;
                $total_proj += $projection;
            }

            $proj->pts = $total_proj/$num_proj;
            $proj->save();
        }
    }
}
