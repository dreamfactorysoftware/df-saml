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

        $base = [
            $path => [
                'get' => [
                    'summary'     => 'getSSO() - Perform authentication',
                    'operationId' => 'getSSO',
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