<?php

namespace DreamFactory\Core\Saml\Services;

use DreamFactory\Core\Saml\Components\DfSaml;
use DreamFactory\Core\Saml\Resources\ACS;
use DreamFactory\Core\Saml\Resources\Metadata;
use DreamFactory\Core\Saml\Resources\SSO;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Resources\System\Environment;

class SAML extends BaseRestService
{
    /** @var DfSaml */
    protected $auth = null;

    /** @var array */
    protected $settings = [];

    /** @var integer */
    protected $defaultRole;

    /** @var string */
    protected $relayState;

    /** @type array Service Resources */
    protected static $resources = [
        Metadata::RESOURCE_NAME => [
            'name'       => Metadata::RESOURCE_NAME,
            'class_name' => Metadata::class,
            'label'      => 'Metadata'
        ],
        ACS::RESOURCE_NAME      => [
            'name'       => ACS::RESOURCE_NAME,
            'class_name' => ACS::class,
            'label'      => 'Assertion Consumer Service'
        ],
        SSO::RESOURCE_NAME      => [
            'name'       => SSO::RESOURCE_NAME,
            'class_name' => SSO::class,
            'label'      => 'Single Sign On Service'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $config = array_get($settings, 'config');
        Session::replaceLookups($config, true);

        if (empty($config)) {
            throw new InternalServerErrorException('No service configuration found for log service.');
        }

        $spBaseUrl = Environment::getURI() . '/api/v2/' . $this->name;

        $samlSettings = [
            'strict' => array_get($config, 'strict', false),
            'sp'     => [
                'entityId'                 => $spBaseUrl . '/metadata',
                'assertionConsumerService' => ['url' => $spBaseUrl . '/acs'],
                'NameIDFormat'             => array_get($config, 'sp_nameIDFormat',
                    'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
                'x509cert'                 => array_get($config, 'sp_x509cert', ''),
                'privateKey'               => array_get($config, 'sp_privateKey', ''),
            ],
            'idp'    => [
                'entityId'            => array_get($config, 'idp_entityId', ''),
                'singleSignOnService' => ['url' => array_get($config, 'idp_singleSignOnService_url', '')],
                'singleLogoutService' => ['url' => array_get($config, 'idp_singleLogoutService_url', '')],
                'x509cert'            => array_get($config, 'idp_x509cert'),
            ],
        ];

        $this->settings = $samlSettings;
        $this->defaultRole = array_get($config, 'default_role');
        $this->relayState = array_get($config, 'relay_state');
        $this->auth = new DfSaml($samlSettings);
    }

    /** @inheritdoc */
    public function getResources($only_handlers = false)
    {
        return ($only_handlers) ? static::$resources : array_values(static::$resources);
    }

    /**
     * @return \DreamFactory\Core\Saml\Components\DfSaml
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int|mixed
     */
    public function getDefaultRole()
    {
        return $this->defaultRole;
    }

    /**
     * @return mixed|string
     */
    public function getRelayState()
    {
        return $this->relayState;
    }

    /** {@inheritdoc} */
    public static function getApiDocInfo($service)
    {
        $base = parent::getApiDocInfo($service);

        $apis = [];
        $models = [];
        foreach (static::$resources as $resourceInfo) {
            $resourceClass = array_get($resourceInfo, 'class_name');

            if (!class_exists($resourceClass)) {
                throw new InternalServerErrorException('Service configuration class name lookup failed for resource ' .
                    $resourceClass);
            }

            $resourceName = array_get($resourceInfo, static::RESOURCE_IDENTIFIER);
            if (Session::checkForAnyServicePermissions($service->name, $resourceName)) {
                $results = $resourceClass::getApiDocInfo($service->name, $resourceInfo);
                if (isset($results, $results['paths'])) {
                    $apis = array_merge($apis, $results['paths']);
                }
                if (isset($results, $results['definitions'])) {
                    $models = array_merge($models, $results['definitions']);
                }
            }
        }

        $base['paths'] = array_merge($base['paths'], $apis);
        $base['definitions'] = array_merge($base['definitions'], $models);
        unset($base['paths']['/saml']['get']['parameters']);

        return $base;
    }
}