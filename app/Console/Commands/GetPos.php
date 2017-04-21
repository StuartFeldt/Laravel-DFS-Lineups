<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class GetPos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:get:pos {pos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get all players at a position';

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
        $pos = $this->argument('pos');
        $slate = env('SLATE');
        $player = Player::where('pos', $pos)->join('projections', 'projections.fd_id', '=', 'fd_players.fd_id')->orderBy('pts', 'desc')->where('slate', $slate)->get();

        $headers = ['Position', 'Name', 'Points', 'Type', 'Salary', 'Id'];
        $data = [];
        foreach($player as $projection) {
            $data[] = [
                $projection->pos, $projection->name, $projection->pts, $projection->type, $projection->salary, $projection->fd_id
            ];
        }

        $this->table($headers, $data);
    }
}
