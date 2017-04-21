<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class GetProj extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:proj:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List Projections Available';

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
        $projections = Projection::selectRaw('count(*) as total, projections.type, max(projections.created_at) as created_at, max(projections.updated_at) as updated_at ')->join('fd_players', 'fd_players.fd_id', '=', 'projections.fd_id')->where('slate', $slate)->groupBy('type')->orderBy('updated_at', 'desc')->get();

        $headers = ['Type', 'Num Proj', 'Created At', 'Updated At'];
        $data = [];
        foreach($projections as $projection) {
            $data[] = [
                $projection->type, $projection->total, $projection->created_at, $projection->updated_at
            ];
        }

        $this->table($headers, $data);
    }
}
