<?php

namespace DreamFactory\Core\Saml;

use DreamFactory\Core\Saml\Models\SAMLConfig;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Enums\ServiceTypeGroups;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'saml',
                    'label'           => 'SAML 2.0',
                    'description'     => 'SAML 2.0 service supporting SSO.',
                    'group'           => ServiceTypeGroups::SSO,
                    'config_handler'  => SAMLConfig::class,
                    'factory'         => function ($config){
                        return new SAML($config);
                    },
                    'access_exceptions' => [
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
                        ]
                    ],
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