<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class QueueLuGen implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $type;
    protected $name;
    protected $min_lus;
    protected $randos;
    protected $starting_lus;
    protected $rounds;
    protected $stacks;
    protected $locks;
    protected $max_pop;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($type, $name, $min_lus, $randos, $starting_lus, $rounds, $stack, $locks, $max_pop)
    {
        $this->type = $type;
        $this->name = $name;
        $this->min_lus = $min_lus;
        $this->randos = $randos;
        $this->starting_lus = $starting_lus;
        $this->rounds = $rounds;
        $this->stacks = $stacks;
        $this->locks = $locks;
        $this->max_pop = $max_pop;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('dfs:generate:lineups', ['type' => $type, 'name' => $name, '--lus' => $min_lus, '--randos' => $randos, '--seed' => $starting_lus, '--rounds' => $rounds, '--stack' => $stack, '--locks' => $locks, '--max-pop' => $max_pop]);
    }
}
