<?php

namespace Devkit\Laravel\Database;

use Illuminate\Database\Eloquent\Builder;

class Criteria
{
    /**
     * @var array<int, array>
     */
    private $operations = array();

    public static function create()
    {
        static::registerBuilderMacro();

        return new static();
    }

    public static function registerBuilderMacro()
    {
        if (method_exists(Builder::class, 'hasGlobalMacro') && Builder::hasGlobalMacro('withCriteria')) {
            return;
        }

        Builder::macro('withCriteria', function (Criteria $criteria) {
            return $criteria->apply($this);
        });
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        $this->operations[] = array('where', func_get_args());

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        $this->operations[] = array('orderBy', func_get_args());

        return $this;
    }

    public function limit($value)
    {
        $this->operations[] = array('limit', array($value));

        return $this;
    }

    public function apply(Builder $builder)
    {
        foreach ($this->operations as $operation) {
            call_user_func_array(array($builder, $operation[0]), $operation[1]);
        }

        return $builder;
    }
}
