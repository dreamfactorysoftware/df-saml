<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Resources\BaseRestResource;

class BaseSamlResource extends BaseRestResource
{
    /** A resource identifier used in swagger doc. */
    const RESOURCE_IDENTIFIER = 'name';

    /**
     * {@inheritdoc}
     */
    protected static function getResourceIdentifier()
    {
        return static::RESOURCE_IDENTIFIER;
    }
}