<?php

namespace App\Console\Commands;

use Cache;
use App\Lineup;
use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class GetJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:jobs:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get jobs queue';

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
        $num = \Redis::connection()->llen('queues:default');
        $jobs = \Redis::connection()->lrange('queues:default', 0, $num);

        $headers = ['id', 'Name', 'Lus', 'Stack', 'Rounds', 'Randos', 'Locks'];
        $data = [];
        foreach($jobs as $i => $job) {
            $job = json_decode($job);
            $data[] = [
                $i, 
                $job->data[1]->name, 
                $job->data[1]->{'--lus'}, 
                $job->data[1]->{'--stack'}, 
                $job->data[1]->{'--rounds'}, 
                $job->data[1]->{'--randos'}, 
                $job->data[1]->{'--locks'}, 
            ];
        }

        $this->table($headers, $data);
    }
}
