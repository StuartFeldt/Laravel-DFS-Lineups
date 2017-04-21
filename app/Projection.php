<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Projection extends Model
{
    public $table = 'projections';
    protected $guarded = [];   

    public function player()
	{
	    return $this->hasOne('App\Player', 'fd_id', 'fd_id');
	}
}
