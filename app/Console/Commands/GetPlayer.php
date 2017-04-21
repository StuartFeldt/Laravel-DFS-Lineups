<?php

namespace App\Console\Commands;

use App\Lineup;
use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class GetPlayer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:get {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get a player by name';

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
        $slate = env('SLATE');
        $player = Player::where('name', 'like', '%' . $name . '%')->join('projections', 'projections.fd_id', '=', 'fd_players.fd_id')->where('slate', $slate)->get();

        $headers = ['Position', 'Name', 'Points', 'Type', 'Salary', "#Lus", 'Id'];
        $data = [];
        foreach($player as $projection) {
            $lus = \DB::select("select * from `lineups` where `slate` = 16502 and (`qb` = '$projection->fd_id' or `rb1` = '$projection->fd_id' or `rb2` = '$projection->fd_id' or `wr1` = '$projection->fd_id' or `wr2` = '$projection->fd_id' or `wr3` = '$projection->fd_id' or `te` = '$projection->fd_id' or `k` = '$projection->fd_id' or `d` = '$projection->fd_id')");

            $data[] = [
                $projection->pos, $projection->name, $projection->pts, $projection->type, $projection->salary, count($lus), $projection->fd_id
            ];
        }

        $this->table($headers, $data);
    }
}
