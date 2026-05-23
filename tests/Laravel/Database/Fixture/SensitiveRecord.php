<?php

namespace Devkit\Tests\Laravel\Database\Fixture;

use Devkit\Laravel\Database\Cast\EncryptedCast;
use Devkit\Laravel\Database\Cast\HashedCast;
use Illuminate\Database\Eloquent\Model;

class SensitiveRecord extends Model
{
    protected $table = 'sensitive_records';

    protected $guarded = array();

    public $timestamps = false;

    protected $casts = array(
        'ssn' => EncryptedCast::class,
        'password' => HashedCast::class,
    );
}
