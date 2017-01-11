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
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;

        $base['paths'][$path]['get'] = [
            'tags'        => [$serviceName],
            'summary'     => 'getSSO() - Perform authentication',
            'operationId' => 'getSSO',
            'consumes'    => [],
            'produces'    => ['application/json', 'application/xml', 'text/csv'],
            'responses'   => [
                '302'     => [
                    'description' => 'Redirect to IdP',
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
            'description' => 'Redirects to IdP login page.'
        ];

        return $base;
    }
}