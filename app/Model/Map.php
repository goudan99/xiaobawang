<?php
namespace App\Model;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Map extends Model
{
	use HasFactory;

	protected $fillable = ['name','machine_id','area','status'];
	
    protected $casts = [
        'area' => 'collection',
    ];
}
