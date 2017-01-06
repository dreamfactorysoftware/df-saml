<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Utility\ResponseFactory;

class Metadata extends BaseRestResource
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

        if(!empty($errors)){
            throw new InternalServerErrorException('Invalid SP metadata. ' . implode(', ', $errors));
        }

        return ResponseFactory::create($metadata, 'text/xml');
    }
}