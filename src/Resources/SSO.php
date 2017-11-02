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

        $base = [
            $path => [
                'get' => [
                    'summary'     => 'Perform authentication',
                    'description' => 'Redirects to IdP login page.',
                    'operationId' => 'get' . $capitalized . 'SSO',
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