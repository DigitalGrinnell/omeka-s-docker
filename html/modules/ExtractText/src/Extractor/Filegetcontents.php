<?php
namespace ExtractText\Extractor;

/**
 * Use file_get_contents to extract text.
 *
 * @see http://php.net/manual/en/function.file-get-contents.php
 */
class Filegetcontents implements ExtractorInterface
{
    public function isAvailable()
    {
        return true;
    }

    public function extract($filePath, array $options = [])
    {
        $offset = isset($options['offset']) ? $options['offset'] : 0;
        if (isset($options['maxlen'])) {
            // file_get_contents() interprets a null maxlen as 0, so we can't
            // set a default maxlen like we can for offset.
            return file_get_contents($filePath, false, null, $offset, $options['maxlen']);
        } else {
            return file_get_contents($filePath, false, null, $offset);
        }
    }
}
