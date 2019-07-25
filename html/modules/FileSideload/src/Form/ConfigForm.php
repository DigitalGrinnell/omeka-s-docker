<?php
namespace FileSideload\Form;

use Zend\Form\Form;
use Zend\Validator\Callback;

class ConfigForm extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'text',
            'name' => 'directory',
            'options' => [
                'label' => 'Sideload directory', // @translate
                'info' => 'Enter the absolute path to the directory where files to be sideloaded will be added. The directory can be anywhere on your server.', // @translate
            ],
            'attributes' => [
                'required' => true,
                'id' => 'directory',
            ],
        ]);
        $this->add([
            'type' => 'checkbox',
            'name' => 'delete_file',
            'options' => [
                'label' => 'Delete sideloaded file?', // @translate
                'info' => 'Do you want to delete a file from the sideload directory after it has been sideloaded? If so, the directory must be server-writable.', // @translate
                'use_hidden_element' => true,
                'checked_value' => 'yes',
                'unchecked_value' => 'no',
            ],
            'attributes' => [
                'id' => 'delete-file',
            ],
        ]);

        $inputFilter = $this->getInputFilter();
        $inputFilter->add([
            'name' => 'directory',
            'required' => true,
            'filters' => [
                ['name' => 'StringTrim'],
            ],
            'validators' => [
                [
                    'name' => 'Callback',
                    'options' => [
                        'messages' => [
                            Callback::INVALID_VALUE => 'The provided sideload directory is not a directory or does not have sufficient permissions.', // @translate
                        ],
                        'callback' => [$this, 'directoryIsValid']
                    ],
                ],
            ],
        ]);
    }

    public function directoryIsValid($dir, $context)
    {
        $dir = new \SplFileInfo($dir);
        $valid = $dir->isDir() && $dir->isExecutable() && $dir->isReadable();
        if (isset($context['delete_file']) && 'yes' === $context['delete_file']) {
            $valid = $valid && $dir->isWritable();
        }
        return $valid;
    }
}
