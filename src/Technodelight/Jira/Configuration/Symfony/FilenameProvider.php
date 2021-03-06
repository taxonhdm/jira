<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Technodelight\GitShell\Api as Git;

class FilenameProvider
{
    const FILENAME = '.jira.yml';

    private $git;

    public function __construct(Git $git)
    {
        $this->git = $git;
    }

    public function localFile()
    {
        try {
            return $this->git->topLevelDirectory() . DIRECTORY_SEPARATOR . self::FILENAME;
        } catch (\Exception $exc) {
            return __DIR__;
        }
    }

    public function globalFile() {
        return getenv('HOME') . DIRECTORY_SEPARATOR . self::FILENAME;
    }
}
