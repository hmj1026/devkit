<?php

namespace Devkit\Laravel\Http\Support;

class UserClientIdCookie extends AbstractCookie
{
    public function name()
    {
        return 'user_client_id';
    }
}
