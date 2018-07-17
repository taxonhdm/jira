<?php

namespace Technodelight\Jira\Configuration\ApplicationConfiguration;

class InstanceConfiguration
{
    private $name;
    private $domain;
    private $username;
    private $password;
    private $isTempoEnabled;

    public static function fromArray(array $config)
    {
        $instance = new self;
        $instance->name = $config['name'];
        $instance->domain = $config['domain'];
        $instance->username = $config['username'];
        $instance->password = $config['password'];
        $instance->isTempoEnabled = $config['tempo'];

        return $instance;
    }

    public function name()
    {
        return $this->name;
    }

    public function domain()
    {
        return $this->domain;
    }

    public function username()
    {
        return $this->username;
    }

    public function password()
    {
        return $this->password;
    }

    /**
     * @return bool|null
     */
    public function isTempoEnabled()
    {
        return $this->isTempoEnabled;
    }

    private function __construct()
    {
    }
}