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
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;

        $base['paths'][$path]['get'] = [
            'tags'        => [$serviceName],
            'summary'     => 'getMetadata() - Gets SAML 2.0 metadata',
            'operationId' => 'getMetadata',
            'consumes'    => [],
            'produces'    => ['application/json', 'application/xml', 'text/csv'],
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
            'description' => 'Generates SAML 2.0 XML metadata.'
        ];

        return $base;
    }
}