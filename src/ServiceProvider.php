<?php

namespace DreamFactory\Core\Saml;

use DreamFactory\Core\Saml\Models\SAMLConfig;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;
use DreamFactory\Core\Components\ServiceDocBuilder;
use DreamFactory\Core\Enums\ServiceTypeGroups;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    use ServiceDocBuilder;

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
                    'default_api_doc' => function ($service){
                        return $this->buildServiceDoc($service->id, SAML::getApiDocInfo($service));
                    },
                    'factory'         => function ($config){
                        return new SAML($config);
                    },
                ])
            );
        });
    }

}