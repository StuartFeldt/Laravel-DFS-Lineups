<?php

namespace App\Console\Commands;

use App\Player;
use App\Projection;
use App\Helpers\GenericRequest;
use Illuminate\Console\Command;

class GetRotoGrinders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:rg:get  {--type=} {--ignore=} {--sport=nfl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pull down RotoGrinders proj';

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
        $type = $this->option('type');
        $sport = $this->option('sport');

        $slate = env('SLATE');
        $ignore = env('IGNORE');
        $ignore_games = explode(',', $ignore);

        if($sport == 'nfl') {
            $urls = [
                "https://rotogrinders.com/projected-stats/nfl-qb.csv?site=fanduel",
                "https://rotogrinders.com/projected-stats/nfl-rb.csv?site=fanduel",
                "https://rotogrinders.com/projected-stats/nfl-wr.csv?site=fanduel",
                "https://rotogrinders.com/projected-stats/nfl-te.csv?site=fanduel",
                "https://rotogrinders.com/projected-stats/nfl-kicker.csv?site=fanduel",
                "https://rotogrinders.com/projected-stats/nfl-defense.csv?site=fanduel",
            ];
        } else if($sport == 'nhl') {
            $urls = [
                "https://rotogrinders.com/projected-stats/nhl-skater.csv?site=fanduel",
                "https://rotogrinders.com/projected-stats/nhl-goalie.csv?site=fanduel",
            ];
        } else {
            $urls = [
                "https://rotogrinders.com/projected-stats/nba-player.csv?site=fanduel"
            ];
        }

        foreach($urls as $url) {
            $handle = GenericRequest::get($url);

            $data = str_getcsv($handle, "\n");

            foreach($data as $row) {
                $pieces = explode(',', $row);
                $player = Player::where('name', 'LIKE', '%' .$pieces[0]. '%')->where('slate', $slate)->first();
                var_dump($pieces);
                if(!$player) {
                    if(in_array($pieces[2], $ignore_games)) {
                        continue;
                    }
                    echo("\nCould not find: " . $pieces[0]);
                    continue;
                }
                $pts = explode('"', $pieces[7]);
                $proj = Projection::firstOrNew(['fd_id' => $player->fd_id, 'type' => $type]);
                $proj->type = $type;
                $proj->pts = $pieces[7];
                $proj->save();
            }
        }
        
    }
}
