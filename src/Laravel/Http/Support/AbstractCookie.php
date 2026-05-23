<?php

namespace Devkit\Laravel\Http\Support;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Cookie;

abstract class AbstractCookie
{
    /**
     * @var string
     */
    protected $value;

    public function __construct($value)
    {
        $this->value = (string) $value;
    }

    abstract public function name();

    public function minutes()
    {
        return 525600;
    }

    public function path()
    {
        return '/';
    }

    public function secure()
    {
        return false;
    }

    public function httpOnly()
    {
        return true;
    }

    public function sameSite()
    {
        return 'lax';
    }

    public function value()
    {
        return $this->value;
    }

    public function toSymfonyCookie()
    {
        return new Cookie(
            $this->name(),
            $this->value(),
            time() + ($this->minutes() * 60),
            $this->path(),
            null,
            $this->secure(),
            $this->httpOnly(),
            false,
            $this->sameSite()
        );
    }

    public function attachTo(Response $response)
    {
        $response->headers->setCookie($this->toSymfonyCookie());

        return $response;
    }
}
