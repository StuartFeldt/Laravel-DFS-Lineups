<?php

namespace App\Console;

use App\Lineup;
use App\Player;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\GenerateLus::class,
        Commands\GetLus::class,
        Commands\InsertFd::class,
        Commands\QueueGenLus::class,
        Commands\GetProj::class,
        Commands\GetPos::class,
        Commands\GetJobs::class,
        Commands\DeleteJob::class,
        Commands\DfsFeeds::class,
        Commands\AddCustom::class,
        Commands\Exclude::class,
        Commands\GetPlayer::class,
        Commands\UpdateLuPts::class,
        Commands\InsertProj::class,
        Commands\MergeProjections::class,
        Commands\GetRotoGrinders::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();

        
        $proj = "all_" . env('SLATE');
        $fd_ids = [];
        $schedule->command("dfs:generate:lineups $proj $proj --lus=5 --randos=5 --rounds=250")->everyMinute()
                    ->before(function () use(&$fd_ids) {
                        for($i = 0; $i < floor(rand(0, 5)); $i++) {
                            $pos = ['qb', 'rb1', 'rb2', 'wr1', 'wr2', 'wr3', 'te', 'k', 'd'];
                             $pos_to_exclude = $pos[array_rand($pos)];

                             $id_to_exclude = Lineup::selectRaw("$pos_to_exclude, count(*) as cnt")
                               ->groupBy($pos_to_exclude)
                               ->where('slate', env('SLATE'))
                               ->orderBy('cnt', 'DESC')
                               ->get()->first();

                               $fd_id = $id_to_exclude->$pos_to_exclude;
                               \Log::info("Deleting $fd_id");
                               Player::where('fd_id', $fd_id)->delete();
                               $fd_ids[] = $fd_id;
                        }
                         

                     })
                     ->after(function ()  use(&$fd_ids) {
                         // Task is complete...
                        foreach($fd_ids as $fd_id) {
                            \Log::info("Restoring $fd_id");
                            Player::withTrashed()->where('fd_id', $fd_id)->restore();
                        }
                        
                     });
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('routes/console.php');
    }
}
