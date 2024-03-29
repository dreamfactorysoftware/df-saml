<?php

namespace DreamFactory\Core\Saml\Services;

use DreamFactory\Core\Saml\Components\DfSaml;
use DreamFactory\Core\Saml\Resources\ACS;
use DreamFactory\Core\Saml\Resources\Metadata;
use DreamFactory\Core\Saml\Resources\SSO;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Utility\Environment;
use Arr;

class SAML extends BaseRestService
{
    /**
     * SAML service provider name.
     */
    const PROVIDER_NAME = 'saml';

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

        if (empty($this->config)) {
            throw new InternalServerErrorException('No service configuration found for log service.');
        }

        $spBaseUrl = Environment::getURI() . '/api/v2/' . $this->name;

        $samlSettings = [
            'strict' => Arr::get($this->config, 'strict', false),
            'sp'     => [
                'entityId'                 => $spBaseUrl . '/metadata',
                'assertionConsumerService' => ['url' => $spBaseUrl . '/acs'],
                'NameIDFormat'             => Arr::get($this->config, 'sp_nameIDFormat', 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress'),
                'x509cert'                 => Arr::get($this->config, 'sp_x509cert', ''),
                'privateKey'               => Arr::get($this->config, 'sp_privateKey', ''),
            ],
            'idp'    => [
                'entityId'            => Arr::get($this->config, 'idp_entityId', ''),
                'singleSignOnService' => ['url' => Arr::get($this->config, 'idp_singleSignOnService_url', '')],
                'singleLogoutService' => ['url' => Arr::get($this->config, 'idp_singleLogoutService_url', '')],
                'x509cert'            => Arr::get($this->config, 'idp_x509cert'),
            ],
        ];

        $this->settings = $samlSettings;
        $this->defaultRole = Arr::get($this->config, 'default_role');
        $this->relayState = Arr::get($this->config, 'relay_state');
        $this->auth = new DfSaml($samlSettings);
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
}