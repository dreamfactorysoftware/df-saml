<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Saml\Services\SAML;

class SSO extends BaseRestResource
{
    const RESOURCE_NAME = 'sso';

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        /** @var SAML $service */
        $service = $this->getParent();
        $service->getAuth()->login($service->getRelayState());
    }
}