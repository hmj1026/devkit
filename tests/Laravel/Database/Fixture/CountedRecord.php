<?php

namespace Devkit\Tests\Laravel\Database\Fixture;

use Devkit\Laravel\Database\Cast\UsesClassCastCompatibility;
use Illuminate\Database\Eloquent\Model;

class CountedRecord extends Model
{
    use UsesClassCastCompatibility;

    protected $table = 'counted_records';

    protected $guarded = array();

    public $timestamps = false;

    protected $casts = array(
        'col' => CountingCast::class,
    );
}
