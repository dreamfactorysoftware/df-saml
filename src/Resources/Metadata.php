<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Utility\ResponseFactory;

class Metadata extends BaseSamlResource
{
    const RESOURCE_NAME = 'metadata';

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        /** @var SAML $service */
        $service = $this->getParent();
        $settings = $service->getAuth()->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!empty($errors)) {
            throw new InternalServerErrorException('Invalid SP metadata. ' . implode(', ', $errors));
        }

        return ResponseFactory::create($metadata, 'text/xml');
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $class = trim(strrchr(static::class, '\\'), '\\');

        $base = [
            $path => [
                'get' => [
                    'summary'     => 'get' . $capitalized . $class . 'Metadata() - Gets SAML 2.0 metadata',
                    'operationId' => 'get' . $capitalized . $class . 'Metadata',
                    'description' => 'Generates SAML 2.0 XML metadata.',
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                        ],
                    ],
                ],
            ],
        ];

        return $base;
    }
}