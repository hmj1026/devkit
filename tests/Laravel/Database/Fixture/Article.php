<?php

namespace Devkit\Tests\Laravel\Database\Fixture;

use Devkit\Database\Contract\Entity\HasAuditLogContract;
use Devkit\Database\Contract\Entity\HasStatusContract;
use Devkit\Database\Contract\Entity\HasUuidContract;
use Devkit\Laravel\Database\Entity\HasAuditLog;
use Devkit\Laravel\Database\Entity\HasStatus;
use Devkit\Laravel\Database\Entity\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Article extends Model implements HasUuidContract, HasStatusContract, HasAuditLogContract
{
    use HasUuid;
    use HasStatus;
    use HasAuditLog;

    protected $table = 'articles';

    protected $guarded = array();

    public $timestamps = false;
}
