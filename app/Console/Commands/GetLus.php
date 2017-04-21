<?php

namespace App\Console\Commands;

use Cache;
use App\Lineup;
use App\Player;
use App\NbaLineup;
use App\NhlLineup;
use App\Projection;
use Illuminate\Console\Command;

class GetLus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:lus:get {--num=10} {--type=} {--excludes=} {--locks=} {--max_exposure=} {--players_max_exposure=} {--players_min_exposure=} {--stack_rate=0} {--double_stack_rate=0} {--sport=nfl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Lineups';
    protected $players;

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
        $num = $this->option('num');
        $sport = $this->option('sport');
        $type = $this->option('type', false) ? $this->option('type') : 'all_'.env('SLATE');
        $locks = $this->option('locks', false);
        $stack_rate = $this->option('stack_rate', false);
        $this->double_stack_rate = $this->option('double_stack_rate', false);
        $excludes = $this->option('excludes', false);
        $max_exposure = $this->option('max_exposure', 1);
        $players_max_exposure = $this->option('players_max_exposure', false);
        $players_min_exposure = $this->option('players_min_exposure', false);
        $this->info($type);

        $this->excluded_players = [];

        if($locks) {
            $locks = explode(',', $locks);
        }

        if($excludes) {
            $excludes = explode(',', $excludes);
        }

        $players_exp = [];
        if($players_max_exposure) {
            $players_max_exposure = explode(',', $players_max_exposure);
            foreach($players_max_exposure as $player) {
                $key_val = explode(':', $player);
                $players_exp[$key_val[0]] = $key_val[1];
            }
        }

        $players_exp_min = [];
        if($players_min_exposure) {
            $players_min_exposure = explode(',', $players_min_exposure);
            foreach($players_min_exposure as $player) {
                $key_val = explode(':', $player);
                $players_exp_min[$key_val[0]] = $key_val[1];
            }
        }
        $players = Player::where('slate', env('SLATE'))->get()->keyBy('fd_id');
        $proj = Projection::where('type', $type)->get()->keyBy('fd_id');
        $this->players = $players;

        if($sport == 'nfl') {
            $lus = $this->getLus($num, $locks, $excludes, $max_exposure, $players_exp, $players_exp_min, $stack_rate);
            $positions = ['qb', 'rb1', 'rb2', 'wr1', 'wr2', 'wr3', 'te', 'k', 'd'];
            $position_headers = ['qb', 'rb', 'rb', 'wr', 'wr', 'wr', 'te', 'k', 'd'];
        } else if ($sport == 'nhl'){
            $lus = $this->getNhlLus($num, $locks, $excludes, $max_exposure, $players_exp, $players_exp_min, $stack_rate);
            $positions = ['c1', 'c2', 'w1', 'w2', 'w3', 'w4', 'd1', 'd2', 'g'];
            $position_headers = ['c', 'c', 'w', 'w', 'w', 'w', 'd', 'd', 'g'];
        } else {
            $lus = $this->getNbaLus($num, $locks, $excludes, $max_exposure, $players_exp, $players_exp_min, $stack_rate);
            $positions = ['pg1', 'pg2', 'sg1', 'sg2', 'sf1', 'sf2', 'pf1', 'pf2', 'c'];
            $position_headers = ['pg', 'pg', 'sg', 'sg', 'sf', 'sf', 'pf', 'pf', 'c'];
        }

        $csv = [];
        $headers = ['Id', 'Name', 'Pos', 'Salary', 'Points', 'Team', 'Game'];
        $uses = [];

        foreach($lus as $i => $lu) {
            $out_lus = [];
            $lu_players = [];

            foreach($positions as $pos) {

                if(!isset($players[$lu[$pos]])) {
                    continue;
                }

                if(isset($uses[$players[$lu[$pos]]->fd_id])) {
                    $uses[$players[$lu[$pos]]->fd_id]++;
                } else {
                    $uses[$players[$lu[$pos]]->fd_id] = 1;
                }

                $lu_players[] = $lu[$pos];
                $out_lus[] = [
                    $players[$lu[$pos]]->fd_id, 
                    $players[$lu[$pos]]->name,
                    $lu[$pos], 
                    $players[$lu[$pos]]->salary,
                    $proj[$lu[$pos]]->pts,
                    $players[$lu[$pos]]->team,
                    $players[$lu[$pos]]->game];
            }
            $out_lus[] = ['', '', '', $lu['salary'], $lu['points'], '', ''];
            $csv[$lu['hash']] = $lu_players;
            $this->table($headers, $out_lus);
        }

        $this->info(implode(',',$position_headers));
        foreach($csv as $hash => $lineup) {
            $this->info(implode(',', $lineup));
        }

        arsort($uses);
        $headers = ['Rank', 'Rank Ch', 'Id', 'Player', 'Pos', 'Team', 'Game', 'Pts', 'Pts Ch', 'Salary',  "Uses", 'Uses ch', 'Exposure', 'Pr. Exp'];
        $data = [];
        $rank = 1;
        foreach($uses as $player => $usage) {

            $datum = [
                            $rank,
                            'NEW',
                            $player, 
                            $players[$player]->name, 
                            $players[$player]->pos, 
                            $players[$player]->team, 
                            $players[$player]->game, 
                            $proj[$player]->pts, 
                            'NEW',
                            $players[$player]->salary, 
                            $usage, 
                            'NEW',
                            round(($usage/sizeof($lus))*100,2) . '%',
                            'NEW'
                        ];

            $cached = Cache::get($player, false);
            if($cached) {

                $datum[1] = $cached[0] - $datum[0];
                $datum[8] = $datum[7] - $cached[7];
                $datum[11] = $datum[10] - $cached[10];
                
                $datum[13] = $cached[12];
            }
            
            Cache::put($player, $datum, 1440);
            
            $data[] = $datum;
            $rank++;
        }
        $this->table($headers, $data);
    }

    public function getNbaLus($num, $locks, $excludes, $limit, $players_exp, $players_exp_min, $stack_rate, $uses = [], $offset = 0, $all_lus = [], $num_stacks = 0, $num_dbl_stacks = 0)
    {
        $lus = NbaLineup::where('slate', env('SLATE'))
                    ->whereNotIn('pg1', $this->excluded_players)
                    ->whereNotIn('pg2', $this->excluded_players)
                    ->whereNotIn('sg1', $this->excluded_players)
                    ->whereNotIn('sg2', $this->excluded_players)
                    ->whereNotIn('sf1', $this->excluded_players)
                    ->whereNotIn('sf2', $this->excluded_players)
                    ->whereNotIn('pf1', $this->excluded_players)
                    ->whereNotIn('pf2', $this->excluded_players)
                    ->whereNotIn('c', $this->excluded_players)
                    ->orderBy('points', 'desc')->take($num)->skip($offset)->get()->toArray();
        
        if(empty($lus)) {
            \Log::info("Out of lineups :(");
            return array_slice($all_lus, 0, $num);
        }
        $positions = ['pg1', 'pg2', 'sg1', 'sg2', 'sf1', 'sf2', 'pf1', 'pf2', 'c'];
        
        foreach($lus as $i => $lu) {
            $using_this_lu = true;
            $player_ids = [];
            $teams = [];
            foreach($positions as $pos) {
                if($excludes) {       
                    if(in_array($lu[$pos], $excludes)) {
                        unset($lus[$i]);
                    }
                }
                $player_ids[] = $lu[$pos];
                
                if($limit) {
                    // for each position in this lineup
                    // if we have used this guy before
                    if( isset( $uses[$lu[$pos]] ) ) {

                        if(isset($players_exp[$lu[$pos]]) && $uses[$lu[$pos]]/$num >= $players_exp[$lu[$pos]]) {
                            $using_this_lu = false;
                            unset($lus[$i]);
                        }
                        // if his usage percentage is >= limit
                        elseif( ( $uses[$lu[$pos]]/$num )  >= $limit ) {

                            // don't use this lineup
                            $this->excluded_players[] = $lu[$pos];
                            $using_this_lu = false;
                            unset($lus[$i]);
                            // 
                        } 
                        
                    } else {
                        // first use of a player
                        $uses[$lu[$pos]] = 1;
                    }

                }
            }

            if($locks) {
                if(!empty(array_diff($locks, $player_ids))) {
                    unset($lus[$i]);
                    continue;
                }
            }

            if($using_this_lu && $limit) {
                foreach($positions as $pos) {
                    $uses[$lu[$pos]]++;
                }
            }

            if($using_this_lu && $stack_rate) {
                $stackable_positions = ['pg1', 'pg2', 'sg1', 'sg2', 'sf1', 'sf2', 'pf1', 'pf2', 'c'];
                $num_stacked = 0;
                    try {
                        // foreach($stackable_positions as $stackable_pos) {
                        //     if($this->players[$lu['qb']]->team == $this->players[$lu[$stackable_pos]]->team) {
                        //         $num_stacked++;
                        //     }
                        // } 
                    $num_stacks++;
                    if($num_stacked >= 2) {
                        // unset($lus[$i]);
                        $num_dbl_stacks++;
                    }
                    } catch (\Exception $e) {
                        \Log::info($e->getMessage());
                    }
            }
            
        }

        \Log::info("number of new lus: " . sizeof($lus));

        $all_lus = array_merge($all_lus, $lus);

        if($stack_rate) {
            \Log::info("Num of stacks we need " . $num*$stack_rate);
            \Log::info("Num of stacks we have " . $num_stacks);
            if($num_stacks < $num*$stack_rate){
                \Log::info("we are stacking");
                $how_many_more_we_need = $num*$stack_rate - $num_stacks;
                \Log::info("We need $how_many_more_we_need more");
                $all_lus = array_slice($all_lus, 0, $num - $how_many_more_we_need);
            }
        }

        if($this->double_stack_rate) {
            \Log::info("Num of dbl stacks we need " . $num*$this->double_stack_rate);
            \Log::info("Num of dbl stacks we have " . $num_dbl_stacks);
            if($num_dbl_stacks < $num*$this->double_stack_rate){
                \Log::info("we are stacking double");
                $how_many_more_we_need = $num*$this->double_stack_rate - $num_dbl_stacks;
                \Log::info("We need $how_many_more_we_need more");
                $all_lus = array_slice($all_lus, 0, $num - $how_many_more_we_need);
            }
        }


        if(sizeof($all_lus) >= $num) {

            \Log::info("returning");
            return array_slice($all_lus, 0, $num);
        }
        \Log::info("Total LUS:" . sizeof($all_lus));
        $offset+=$num;
        return $this->getNbaLus($num, $locks, $excludes, $limit, $players_exp, $players_exp_min, $stack_rate, $uses, $offset, $all_lus, $num_stacks, $num_dbl_stacks);
    }

    public function getNhlLus($num, $locks, $excludes, $limit, $players_exp, $players_exp_min, $stack_rate, $uses = [], $offset = 0, $all_lus = [], $num_stacks = 0, $num_dbl_stacks = 0)
    {
        $lus = NhlLineup::where('slate', env('SLATE'))
                    ->whereNotIn('c1', $this->excluded_players)
                    ->whereNotIn('c2', $this->excluded_players)
                    ->whereNotIn('w1', $this->excluded_players)
                    ->whereNotIn('w2', $this->excluded_players)
                    ->whereNotIn('w3', $this->excluded_players)
                    ->whereNotIn('w4', $this->excluded_players)
                    ->whereNotIn('d1', $this->excluded_players)
                    ->whereNotIn('d2', $this->excluded_players)
                    ->whereNotIn('g', $this->excluded_players)
                    ->orderBy('points', 'desc')->take($num)->skip($offset)->get()->toArray();
        
        if(empty($lus)) {
            \Log::info("Out of lineups :(");
            return array_slice($all_lus, 0, $num);
        }
        $positions = ['c1', 'c2', 'w1', 'w2', 'w3', 'w4', 'd1', 'd2', 'g'];
        
        foreach($lus as $i => $lu) {
            $using_this_lu = true;
            $player_ids = [];
            $teams = [];
            foreach($positions as $pos) {
                if($excludes) {       
                    if(in_array($lu[$pos], $excludes)) {
                        unset($lus[$i]);
                    }
                }
                $player_ids[] = $lu[$pos];
                
                if($limit) {
                    // for each position in this lineup
                    // if we have used this guy before
                    if( isset( $uses[$lu[$pos]] ) ) {

                        if(isset($players_exp[$lu[$pos]]) && $uses[$lu[$pos]]/$num >= $players_exp[$lu[$pos]]) {
                            $using_this_lu = false;
                            unset($lus[$i]);
                        }
                        // if his usage percentage is >= limit
                        elseif( ( $uses[$lu[$pos]]/$num )  >= $limit ) {

                            // don't use this lineup
                            $this->excluded_players[] = $lu[$pos];
                            $using_this_lu = false;
                            unset($lus[$i]);
                            // 
                        } 
                        
                    } else {
                        // first use of a player
                        $uses[$lu[$pos]] = 1;
                    }

                }
            }

            if($locks) {
                if(!empty(array_diff($locks, $player_ids))) {
                    unset($lus[$i]);
                    continue;
                }
            }

            if($using_this_lu && $limit) {
                foreach($positions as $pos) {
                    $uses[$lu[$pos]]++;
                }
            }

            if($using_this_lu && $stack_rate) {
                $stackable_positions = ['w1', 'w2', 'w3', 'w4', 'c1', 'c2', 'd1', 'd2'];
                $num_stacked = 0;
                    try {
                        foreach($stackable_positions as $stackable_pos) {
                            if($this->players[$lu['qb']]->team == $this->players[$lu[$stackable_pos]]->team) {
                                $num_stacked++;
                            }
                        } 
                    $num_stacks++;
                    if($num_stacked >= 2) {
                        // unset($lus[$i]);
                        $num_dbl_stacks++;
                    }
                    } catch (\Exception $e) {
                        \Log::info($e->getMessage());
                    }
            }
            
        }

        \Log::info("number of new lus: " . sizeof($lus));

        $all_lus = array_merge($all_lus, $lus);

        if($stack_rate) {
            \Log::info("Num of stacks we need " . $num*$stack_rate);
            \Log::info("Num of stacks we have " . $num_stacks);
            if($num_stacks < $num*$stack_rate){
                \Log::info("we are stacking");
                $how_many_more_we_need = $num*$stack_rate - $num_stacks;
                \Log::info("We need $how_many_more_we_need more");
                $all_lus = array_slice($all_lus, 0, $num - $how_many_more_we_need);
            }
        }

        if($this->double_stack_rate) {
            \Log::info("Num of dbl stacks we need " . $num*$this->double_stack_rate);
            \Log::info("Num of dbl stacks we have " . $num_dbl_stacks);
            if($num_dbl_stacks < $num*$this->double_stack_rate){
                \Log::info("we are stacking double");
                $how_many_more_we_need = $num*$this->double_stack_rate - $num_dbl_stacks;
                \Log::info("We need $how_many_more_we_need more");
                $all_lus = array_slice($all_lus, 0, $num - $how_many_more_we_need);
            }
        }


        if(sizeof($all_lus) >= $num) {

            \Log::info("returning");
            return array_slice($all_lus, 0, $num);
        }
        \Log::info("Total LUS:" . sizeof($all_lus));
        $offset+=$num;
        return $this->getNhlLus($num, $locks, $excludes, $limit, $players_exp, $players_exp_min, $stack_rate, $uses, $offset, $all_lus, $num_stacks, $num_dbl_stacks);
    }

    public function getLus($num, $locks, $excludes, $limit, $players_exp, $players_exp_min, $stack_rate, $uses = [], $offset = 0, $all_lus = [], $num_stacks = 0, $num_dbl_stacks = 0)
    {
        $lus = Lineup::where('slate', env('SLATE'))
                    ->whereNotIn('qb', $this->excluded_players)
                    ->whereNotIn('rb1', $this->excluded_players)
                    ->whereNotIn('rb2', $this->excluded_players)
                    ->whereNotIn('wr1', $this->excluded_players)
                    ->whereNotIn('wr2', $this->excluded_players)
                    ->whereNotIn('wr3', $this->excluded_players)
                    ->whereNotIn('te', $this->excluded_players)
                    ->whereNotIn('k', $this->excluded_players)
                    ->whereNotIn('d', $this->excluded_players)
                    ->orderBy('points', 'desc')->take($num)->skip($offset)->get()->toArray();
        
        if(empty($lus)) {
            \Log::info("Out of lineups :(");
            return array_slice($all_lus, 0, $num);
        }
        $positions = ['qb', 'rb1', 'rb2', 'wr1', 'wr2', 'wr3', 'te', 'k', 'd'];
        
        foreach($lus as $i => $lu) {
            $using_this_lu = true;
            $player_ids = [];
            $teams = [];
            foreach($positions as $pos) {
                if($excludes) {       
                    if(in_array($lu[$pos], $excludes)) {
                        unset($lus[$i]);
                    }
                }
                $player_ids[] = $lu[$pos];
                
                if($limit) {
                    // for each position in this lineup
                    // if we have used this guy before
                    if( isset( $uses[$lu[$pos]] ) ) {

                        if(isset($players_exp[$lu[$pos]]) && $uses[$lu[$pos]]/$num >= $players_exp[$lu[$pos]]) {
                            $using_this_lu = false;
                            unset($lus[$i]);
                        }
                        // if his usage percentage is >= limit
                        elseif( ( $uses[$lu[$pos]]/$num )  >= $limit ) {

                            // don't use this lineup
                            $this->excluded_players[] = $lu[$pos];
                            $using_this_lu = false;
                            unset($lus[$i]);
                            // 
                        } 
                        
                    } else {
                        // first use of a player
                        $uses[$lu[$pos]] = 1;
                    }

                }
            }

            if($locks) {
                if(!empty(array_diff($locks, $player_ids))) {
                    unset($lus[$i]);
                    continue;
                }
            }

            if($using_this_lu && $limit) {
                foreach($positions as $pos) {
                    $uses[$lu[$pos]]++;
                }
            }

            if($using_this_lu && $stack_rate) {
                $stackable_positions = ['te', 'wr1', 'wr2', 'wr3'];
                $num_stacked = 0;
                    try {
                        foreach($stackable_positions as $stackable_pos) {
                            if($this->players[$lu['qb']]->team == $this->players[$lu[$stackable_pos]]->team) {
                                $num_stacked++;
                            }
                        } 
                    $num_stacks++;
                    if($num_stacked >= 2) {
                        // unset($lus[$i]);
                        $num_dbl_stacks++;
                    }
                    } catch (\Exception $e) {
                        \Log::info($e->getMessage());
                    }
            }
            
        }

        \Log::info("number of new lus: " . sizeof($lus));

        $all_lus = array_merge($all_lus, $lus);

        if($stack_rate) {
            \Log::info("Num of stacks we need " . $num*$stack_rate);
            \Log::info("Num of stacks we have " . $num_stacks);
            if($num_stacks < $num*$stack_rate){
                \Log::info("we are stacking");
                $how_many_more_we_need = $num*$stack_rate - $num_stacks;
                \Log::info("We need $how_many_more_we_need more");
                $all_lus = array_slice($all_lus, 0, $num - $how_many_more_we_need);
            }
        }

        if($this->double_stack_rate) {
            \Log::info("Num of dbl stacks we need " . $num*$this->double_stack_rate);
            \Log::info("Num of dbl stacks we have " . $num_dbl_stacks);
            if($num_dbl_stacks < $num*$this->double_stack_rate){
                \Log::info("we are stacking double");
                $how_many_more_we_need = $num*$this->double_stack_rate - $num_dbl_stacks;
                \Log::info("We need $how_many_more_we_need more");
                $all_lus = array_slice($all_lus, 0, $num - $how_many_more_we_need);
            }
        }


        if(sizeof($all_lus) >= $num) {

            return array_slice($all_lus, 0, $num);
        }
        \Log::info("Total LUS:" . sizeof($all_lus));
        $offset+=$num;
        return $this->getLus($num, $locks, $excludes, $limit, $players_exp, $players_exp_min, $stack_rate, $uses, $offset, $all_lus, $num_stacks, $num_dbl_stacks);
    }
}
