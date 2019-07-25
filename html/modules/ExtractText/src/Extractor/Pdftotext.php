<?php
namespace ExtractText\Extractor;

use Omeka\Stdlib\Cli;

/**
 * Use pdftotext to extract text.
 *
 * @see https://linux.die.net/man/1/pdftotext
 */
class Pdftotext implements ExtractorInterface
{
    protected $cli;

    public function __construct(Cli $cli)
    {
        $this->cli = $cli;
    }

    public function isAvailable()
    {
        return (bool) $this->cli->getCommandPath('pdftotext');
    }

    public function extract($filePath, array $options = [])
    {
        $commandPath = $this->cli->getCommandPath('pdftotext');
        if (false === $commandPath) {
            return false;
        }
        $commandArgs = [$commandPath, '-enc UTF-8'];
        if (isset($options['f'])) {
            $commandArgs[] = sprintf('-f %s', escapeshellarg($options['f']));
        }
        if (isset($options['l'])) {
            $commandArgs[] = sprintf('-l %s', escapeshellarg($options['l']));
        }
        $commandArgs[] = escapeshellarg($filePath);
        $commandArgs[] = '-';
        $command = implode(' ', $commandArgs);
        return $this->cli->execute($command);
    }
}
