<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use Illuminate\Console\Command;

class InsertProj extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:insert:proj {--type=} {--filename=} {--slate=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert';

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
        // load filename
        // find if player exists in fd_players
        // if it does, upsert with slate/player-d
        // otherwise save and display as an errored player to be fixed

        ini_set('auto_detect_line_endings',TRUE);
        $csv_name = $this->option('filename');
        $type = $this->option('type');
        $slate = env('SLATE');
        $ignore = env('IGNORE');
        $ignore_games = explode(',', $ignore);
        \Log::info($this->option('filename'));
        // load filename

        $players = [];
        $row = 0;
        if (($handle = fopen(storage_path() . "/dfs/" .$csv_name, "r")) !== FALSE) {
            while (($data = fgetcsv($handle)) !== false) {
                $row++;
                // if($row > 1) {
                    $player = Player::where('name', 'like', '%' .trim($data[0]).'%')->where('slate', $slate)->first();

                    if(!$player) {
                        $player = Player::where('name', 'like', '%' .trim($data[0]).'%')->first();
                    }

                    if(!$player) {
                        echo "\ncannot Find " . $data[0];
                        continue;
                    }

                    if(in_array($player->team, $ignore_games)) {
                        continue;
                    }


                    $proj = Projection::firstOrNew(['fd_id' => $player->fd_id, 'type' => $type]);
                    $proj->type = $type;
                    $proj->pts = floatval($data[1]);
                    $proj->save();

                // }
            }
            fclose($handle);
        }
    }
}
