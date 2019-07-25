<?php
namespace ExtractText\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use doc2txt to extract text.
 *
 * @see http://docx2txt.sourceforge.net/
 */
class Docx2txt implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('docx2txt');
    }

    public function extract($filePath, array $options = [])
    {
        $commandPath = $this->cli->getCommandPath('docx2txt');
        if (false === $commandPath) {
            return false;
        }
        $commandArgs = [$commandPath, escapeshellarg($filePath), '-'];
        $command = implode(' ', $commandArgs);
        return $this->cli->execute($command);
    }
}
