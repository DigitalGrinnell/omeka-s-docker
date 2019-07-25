<?php
namespace ExtractText\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use odt2txt to extract text.
 *
 * @see https://linux.die.net/man/1/odt2txt
 */
class Odt2txt implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('odt2txt');
    }

    public function extract($filePath, array $options = [])
    {
        $commandPath = $this->cli->getCommandPath('odt2txt');
        if (false === $commandPath) {
            return false;
        }
        $commandArgs = [$commandPath, '--encoding=UTF-8', $filePath];
        $command = implode(' ', $commandArgs);
        return $this->cli->execute($command);
    }
}
