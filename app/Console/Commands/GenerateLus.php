<?php

namespace App\Console\Commands;

use App\Team;
use App\Lineup;
use App\NbaTeam;
use App\NhlTeam;
use App\NbaLineup;
use App\NhlLineup;
use App\Projection;
use App\TeamHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Console\Command;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class GenerateLus extends Command
{

    use InteractsWithQueue, Queueable, SerializesModels;

    // rotogrinders: dfs:rg:get
    // https://www.fantasypros.com/nfl/daily-fantasy-lineup-optimizer.php
    // https://dailyfantasynerd.com/optimizer/fanduel/nfl?week=3
    // http://www.fftoday.com/rankings/playerwkproj.php?Season=2016&GameWeek=4&PosID=10&LeagueID=185034
    // https://swishanalytics.com/optimus/nfl/daily-fantasy-projections


    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dfs:generate:lineups {type} {name} {--lus=15} {--randos=3} {--seed=10} {--rounds=10} {--stack=} {--locks=} {--max_pop=100} {--output} {--queue=} {--sport=nfl}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "lineups";

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
        
        $start  = microtime(true);
        $type = $this->argument('type');
        $this->lus_name = $this->argument('name');
        $min_lus = $this->option('lus');
        $starting_lus = $this->option('seed');
        $rounds = $this->option('rounds');
        $randos = $this->option('randos');
        $sport = $this->option('sport');
        $max_pop = $this->option('max_pop') < $min_lus*2 ? $min_lus*2 : $this->option('max_pop') ;
        $this->stack = $this->option('stack', false);
        $this->locks = $this->option('locks', false);

        $this->helper = new TeamHelper($sport);

        if($this->locks) {
            $this->locks = explode(',', $this->locks);
        }

        $already_generated = Lineup::where('slate', env('SLATE'))->get()->keyBy('hash');
        $projections = Projection::where('type', $type)->with('player')->get();
        $this->type = $type;
        $players = [];

        foreach($projections as $projection) {
            if(!isset($projection->player)) {
                continue;
            }
            if(isset($players[strtolower($projection->player->pos)])) {
                $players[strtolower($projection->player->pos)][] = $projection;
            } else {
                $players[strtolower($projection->player->pos)] = [$projection];
            }
        }

        $this->players = $players;
        $this->position_counts = $this->helper->countPositions($players);

        $population = $this->generateRandomLineups($starting_lus);

        $this->output->progressStart($rounds);
        for($i = 0; $i < $rounds; $i++) {
            $children = [];
            foreach($population as $m => $mother) {
                foreach($population as $f => $father) {
                    if($m == $f) {
                        continue;
                    }
                    $offspring = $this->mate($mother, $father);
                    if($offspring->isValid && !isset($population[$offspring->getHash()]) && !isset($already_generated[$offspring->getHash()])) {
                        $children[$offspring->getHash()] = $offspring;
                    }
                }
            }

            $population += $children;

            uasort($population, function($a, $b) {
                return $b->points <=> $a->points;
            });

            $slicept = 10;
            $cutoff = ceil(sizeof($population) / ($slicept)) > $min_lus*2 ? ceil(sizeof($population) / ($slicept)) : $min_lus*2;
            $cutoff = $cutoff > $max_pop ? $max_pop : $cutoff;
            $population = array_slice($population, 0, $cutoff);

            // save some lineup progress
            foreach($population as $team) {
                $team->save($this->lus_name, $this->type); 
            }

            if($i+1 < $rounds) {
                $population += $this->generateRandomLineups($randos);
            }

            $this->output->progressAdvance();
        }

        $numPrinted = 0;
        $this->output->progressFinish();
        foreach($population as $team) {
            if($numPrinted > $min_lus) {
                break;
            }
            $data = $team->print();
            $this->table($data['headers'], $data['data']);
            $team->save($this->lus_name, $this->type);
            $numPrinted++;
        }
    }

    private function mate($mother, $father, $trait_dominance = .90, $mutate_chance = .1) 
    {

        $child = $this->helper->newTeam();
        $pos_values = $this->helper->getPositions();
        
        $child->mother = $mother;
        $child->father = $father;

        foreach($pos_values as $pos) {
            if(rand(0,1) > 0) {
                $mother_pos_dominance = $mother->$pos->pts/$mother->$pos->player->salary;
                $father_pos_dominance = $father->$pos->pts/$father->$pos->player->salary;
            } else {
                $mother_pos_dominance = $mother->$pos->pts;
                $father_pos_dominance = $father->$pos->pts;
            }

            // pts/dollar is dominant trate we are evolving for
            if($mother_pos_dominance > $father_pos_dominance) {
                $child->$pos = $mother->$pos;
            } else {
                $child->$pos = $father->$pos;
            }

            // trait domination doesn't happen every time
            if((rand(0,100) /100) > $trait_dominance) {
                if(rand(0,1) > 0) {
                    $child->$pos = $mother->$pos;
                } else {
                    $child->$pos = $father->$pos;
                }
            }

            // this position could mutate
            $chance = rand(0, 1000) / 1000;
            if($chance < $mutate_chance) {
                $pos = $this->helper->translatePos($pos);
                $child->pos = $this->players[$pos][rand(0, sizeof($this->players[$pos]) - 1)];
            }

        }
        $child->init($this->stack, $this->locks);
        return $child;
    }

    private function generateRandomLineups($num)
    {
        $lus = [];
        while(sizeof($lus) < $num) {
            $team = $this->helper->createTeam($this->players, $this->position_counts, $this->stack, $this->locks);

            if($team->isValid) {
                $lus[$team->getHash()] = $team;
            }
            if(sizeof($lus) >= $num) {
                break;
            }
        }

        return $lus;
    }
}
