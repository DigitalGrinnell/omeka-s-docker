<?php
namespace ExtractText\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use catdoc to extract text.
 *
 * @see https://linux.die.net/man/1/catdoc
 */
class Catdoc implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('catdoc');
    }

    public function extract($filePath, array $options = [])
    {
        $commandPath = $this->cli->getCommandPath('catdoc');
        if (false === $commandPath) {
            return false;
        }
        $commandArgs = [$commandPath, '-d utf-8', escapeshellarg($filePath)];
        $command = implode(' ', $commandArgs);
        return $this->cli->execute($command);
    }
}
