<?php

namespace Devkit\Laravel\Audit;

use Jenssegers\Agent\Agent;

class AgentSupport
{
    /**
     * @var Agent
     */
    protected $agent;

    public function __construct(Agent $agent = null)
    {
        $this->agent = $agent ?: new Agent();
    }

    public function device()
    {
        return $this->agent->device();
    }

    public function browser()
    {
        return $this->agent->browser();
    }

    public function platform()
    {
        return $this->agent->platform();
    }

    public static function sanitizeHeaders(array $headers)
    {
        $sanitized = array();

        foreach ($headers as $name => $value) {
            $lower = strtolower($name);
            $sanitized[$name] = in_array($lower, array('authorization', 'cookie', 'set-cookie'), true)
                ? array('[redacted]')
                : $value;
        }

        return $sanitized;
    }
}
