<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class AddCustom extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:custom:add {id} {pts}';

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
        $id = $this->argument('id');
        $pts = $this->argument('pts');
        $slate = env('SLATE');
        
        $proj = Projection::firstOrNew(['fd_id' => $id, 'type' => "custom_".$slate]);
        $proj->pts = $pts;
        $proj->save();

        $this->call('dfs:proj:merge');
        $this->call('dfs:update_lu_pts', ['type' => 'all_'. $slate]);
    }
}
