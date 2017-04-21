<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Player extends Model
{
	use SoftDeletes;
	public $table = 'fd_players';   
	protected $guarded = [];
}
