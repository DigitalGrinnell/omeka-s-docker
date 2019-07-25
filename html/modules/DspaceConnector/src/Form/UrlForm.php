<?php
namespace DspaceConnector\Form;

use Zend\Form\Form;
use Zend\Form\Element\Url;
use Zend\Form\Element\Text;

class UrlForm extends Form
{
    public function init()
    {
        $this->setAttribute('action', 'dspace-connector/import');
        $this->add([
            'name' => 'api_url',
            'type' => Url::class,
            'options' => [
                'label' => 'DSpace site URL', // @translate
                'info' => 'The URL of the repository you want to connect to (DSpace 5.6 or higher) Fill this in, then click "Get collections and communities" to browse what you want to import.', // @translate
            ],
            'attributes' => [
                'id' => 'api-url',
                'required' => 'true',
            ],
        ]);

        $this->add([
            'name' => 'endpoint',
            'type' => Text::class,
            'options' => [
                'label' => 'Endpoint', // @translate
                'info' => 'The endpoint for the API', // @translate
            ],
            'attributes' => [
                'id' => 'endpoint',
                'required' => 'false',
                'value' => 'rest',
            ],
        ]);

        $this->add([
            'name' => 'limit',
            'type' => Text::class,
            'options' => [
                'label' => 'Limit', // @translate
                'info' => 'The maximum number of results to retrieve at once from DSpace. If you notice errors or missing data, try lowering this number. Increasing it might make imports faster.', // @translate
            ],
            'attributes' => [
                'id' => 'limit',
                'required' => 'false',
                'value' => '100',
            ],
        ]);
    }
}
