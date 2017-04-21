<?php

namespace App\Console\Commands;

use Queue;
use Artisan;
use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class QueueGenLus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:queue {name} {--lus=10} {--randos=3} {--seed=10} {--rounds=100} {--stack=} {--locks=} {--max_pop=100} {--output} {--sport=nfl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'queue gen lus';

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
        $name = $this->argument('name');
        $min_lus = $this->option('lus');
        $starting_lus = $this->option('seed');
        $rounds = $this->option('rounds');
        $randos = $this->option('randos');
        $max_pop = $this->option('max_pop');
        $stack = $this->option('stack', false);
        $locks = $this->option('locks', false);
        $sport = $this->option('sport');
        $type = 'all_' . env('SLATE');
        \Log::info($type);

        Artisan::queue('dfs:generate:lineups', ['--queue' => 'default', 'type' => $type, 'name' => $name, '--lus' => $min_lus, '--randos' => $randos, '--seed' => $starting_lus, '--rounds' => $rounds, '--stack' => $stack, '--locks' => $locks, '--max_pop' => $max_pop, '--sport' => $sport]);
        
    }
}
