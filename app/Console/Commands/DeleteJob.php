<?php

namespace App\Console\Commands;

use Cache;
use App\Lineup;
use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class DeleteJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:jobs:delete {index}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete jobs queue';

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
        $index = $this->argument('index');
        \Redis::connection()->lset('queues:default', $index, "delete");
        \Redis::connection()->lrem('queues:default', 1, "delete");

        $this->call('dfs:jobs:list');
    }
}
