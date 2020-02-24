<?php

namespace DreamFactory\Core\Saml;

use DreamFactory\Core\Enums\LicenseLevel;
use DreamFactory\Core\Saml\Models\OktaConfig;
use DreamFactory\Core\Saml\Models\SAMLConfig;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $access_exceptions = [
            [
                'verb_mask' => 1,
                'resource'  => 'sso',
            ],
            [
                'verb_mask' => 2,
                'resource'  => 'acs',
            ],
            [
                'verb_mask' => 1,
                'resource'  => 'metadata',
            ],
        ];

        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df) use ($access_exceptions) {
            $df->addType(
                new ServiceType([
                    'name'                  => 'saml',
                    'label'                 => 'SAML 2.0',
                    'description'           => 'SAML 2.0 service supporting SSO.',
                    'group'                 => ServiceTypeGroups::SSO,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => SAMLConfig::class,
                    'factory'               => function ($config) {
                        return new SAML($config);
                    },
                    'access_exceptions'     => $access_exceptions,
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'                  => 'okta_saml',
                    'label'                 => 'Okta SAML',
                    'description'           => 'Okta service supporting SSO.',
                    'group'                 => ServiceTypeGroups::SSO,
                    'subscription_required' => LicenseLevel::SILVER,
                    'config_handler'        => OktaConfig::class,
                    'factory'               => function ($config) {
                        return new SAML($config);
                    },
                    'access_exceptions'     => $access_exceptions,
                ])
            );
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
