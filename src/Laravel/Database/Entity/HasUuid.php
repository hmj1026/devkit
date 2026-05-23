<?php

namespace Devkit\Laravel\Database\Entity;

use Illuminate\Support\Str;

trait HasUuid
{
    public static function bootHasUuid()
    {
        static::creating(function ($model) {
            $column = $model->getUuidColumn();

            if (!$model->getAttribute($column)) {
                $model->setAttribute($column, (string) Str::uuid());
            }
        });
    }

    public function getUuid()
    {
        return $this->getAttribute($this->getUuidColumn());
    }

    public function getUuidColumn()
    {
        return defined('static::UUID_COLUMN') ? static::UUID_COLUMN : 'uuid';
    }

    public function scopeWhereUuid($query, $uuid)
    {
        return $query->where($this->getUuidColumn(), $uuid);
    }

    public static function findByUuid($uuid)
    {
        $instance = new static();

        return static::query()->where($instance->getUuidColumn(), $uuid)->first();
    }
}
