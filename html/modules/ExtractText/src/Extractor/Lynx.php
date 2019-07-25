<?php
namespace ExtractText\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use lynx to extract text.
 *
 * @see https://linux.die.net/man/1/lynx
 */
class Lynx implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('lynx');
    }

    public function extract($filePath, array $options = [])
    {
        $commandPath = $this->cli->getCommandPath('lynx');
        if (false === $commandPath) {
            return false;
        }
        // Must use -force_html or lynx will return markup for files without an
        // .html extension: https://bugs.launchpad.net/ubuntu/+source/lynx/+bug/1112568
        $commandArgs = [$commandPath, '-dump', '-nolist', '-force_html', escapeshellarg($filePath)];
        $command = implode(' ', $commandArgs);
        return $this->cli->execute($command);
    }
}
