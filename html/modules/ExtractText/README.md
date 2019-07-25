# Extract Text

Extract text from files to make them searchable and machine readable.

Once installed and active, this module has the following features:

- The module adds an "extracted text" property where it sets extracted text to
  media and items.
- When adding a media, the module will automatically extract text from the file
  and set the text to the media.
- When adding or editing an item, the module will automatically aggregate the
  media text (in order) and set the text to the item.
- When editing an item or batch editing items, the user can choose to refresh or
  clear the extracted text.
- The user can view the module configuration page to see which extractors are
  available on their system.

## Supported file formats:

- DOC (application/msword)
- DOCX (application/vnd.openxmlformats-officedocument.wordprocessingml.document)
- HTML (text/html)
- ODT (application/vnd.oasis.opendocument.text)
- PDF (application/pdf)
- RTF (application/rtf)
- TXT (text/plain)

Note that some file extensions or media types may be disallowed in your global
settings.

## Extractors:

### catdoc

Used to extract text from DOC and RTF files. Requires [catdoc](https://linux.die.net/man/1/catdoc).

### docx2txt

Used to extract text from DOCX files. Requires [docx2txt](http://docx2txt.sourceforge.net/).

### filegetcontents

Used to extract text from TXT files. No requirements.

### lynx

Used to extract text from HTML files. Requires [lynx](https://linux.die.net/man/1/lynx).

### odt2txt

Used to extract text from ODT files. Requires [odt2txt](https://linux.die.net/man/1/odt2txt).

### pdftotext

Used to extract text from PDF files. Requires [pdftotext](https://linux.die.net/man/1/pdftotext),
a part of the poppler-utils package.

## Disabling text extraction

You can disable text extraction for a specific media type by setting the media
type alias to `null` in the "extract_text_extractors" service config in your
local configuration file (config/local.config.php). For example, if you want to
disable extraction for TXT (text/plain) files, add the following:

```php
'extract_text_extractors' => [
    'aliases' => [
        'text/plain' => null,
    ],
],
```
