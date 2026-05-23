<?php

namespace Devkit\Laravel\Database\Entity;

trait HasStatus
{
    public function initializeHasStatus()
    {
        $this->casts[$this->getStatusColumn()] = 'integer';
    }

    public function isActive()
    {
        return (string) $this->getAttribute($this->getStatusColumn()) === (string) $this->getActiveStatusValue();
    }

    public function activate()
    {
        $this->setAttribute($this->getStatusColumn(), $this->getActiveStatusValue());
        $this->save();

        return $this;
    }

    public function deactivate()
    {
        $this->setAttribute($this->getStatusColumn(), $this->getInactiveStatusValue());
        $this->save();

        return $this;
    }

    public function getStatusColumn()
    {
        return defined('static::STATUS_COLUMN') ? static::STATUS_COLUMN : 'status';
    }

    public function getActiveStatusValue()
    {
        return defined('static::ACTIVE_STATUS') ? static::ACTIVE_STATUS : 1;
    }

    public function getInactiveStatusValue()
    {
        return defined('static::INACTIVE_STATUS') ? static::INACTIVE_STATUS : 0;
    }
}
