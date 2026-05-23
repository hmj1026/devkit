<?php

namespace Devkit\Laravel\Audit\Contract;

interface LoginLogTargetContract
{
    public function saveLogin(array $entry);
}
