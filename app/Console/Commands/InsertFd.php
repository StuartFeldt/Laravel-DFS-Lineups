<?php

namespace App\Console\Commands;

use App\Player;
use Illuminate\Console\Command;

class InsertFd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:insert:fd {filename}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert fd player list';

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
        ini_set('auto_detect_line_endings',TRUE);
        $csv_name = $this->argument('filename');
        \Log::info($this->argument('filename'));
        // load filename

        $players = [];
        $row = 0;
        if (($handle = fopen(storage_path() . "/dfs/" .$csv_name, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                if($row > 1) {
                    $ids = explode('-', $data[0]);
                    $player = Player::firstOrNew(['fd_id' => $data[0]]);
                    $player->slate = $ids[0];
                    $player->pos = $data[1];
                    $player->name = $data[2] . " " . $data[4];
                    $player->salary = $data[7];
                    $player->team = $data[9];
                    $player->opp = $data[10];
                    $player->game = $data[8];
                    $player->save();
                }
            }
            fclose($handle);
        }
        // foreach create or new based on slate/player-id save
    }
}
