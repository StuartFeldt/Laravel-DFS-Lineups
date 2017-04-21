<?php

namespace App\Console\Commands;

use App\Player;
use Illuminate\Console\Command;

class DfsFeeds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:feeds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Dfs feeds';

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
        $feed = \Feeds::make(['http://www.rotoworld.com/rss/feed.aspx?sport=nfl&ftype=news&count=12&format=rss',
                'http://www.nfl.com/rss/rsslanding?searchString=home',

            ], 100, true);
        foreach($feed->get_items() as $item) {
            $this->info($item->get_title());
            $this->info($item->get_description() . "\n");
        }
    }
}
