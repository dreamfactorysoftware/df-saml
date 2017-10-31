<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Saml\Services\SAML;

class SSO extends BaseSamlResource
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
                    'summary'     => 'get' . $capitalized . $class . 'SSO() - Perform authentication',
                    'operationId' => 'get' . $capitalized . $class . 'SSO',
                    'description' => 'Redirects to IdP login page.',
                    'responses'   => [
                        '302' => [
                            'description' => 'Redirect to IdP',
                        ],
                    ],
                ],
            ],
        ];

        return $base;
    }
}