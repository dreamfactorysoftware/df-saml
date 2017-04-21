<?php

namespace DreamFactory\Core\Saml\Models;

use DreamFactory\Core\Components\AppRoleMapper;
use DreamFactory\Core\Models\BaseServiceConfigModel;
use DreamFactory\Core\Models\Role;

class SAMLConfig extends BaseServiceConfigModel
{
    use AppRoleMapper;

    /** @var string */
    protected $table = 'saml_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'default_role',
        'sp_nameIDFormat',
        'sp_x509cert',
        'sp_privateKey',
        'relay_state',
        'idp_entityId',
        'idp_singleSignOnService_url',
        'idp_x509cert',
        'icon_class',
    ];

    /** @var array */
    protected $casts = [
        'service_id'   => 'integer',
        'default_role' => 'integer',
    ];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'default_role':
                $roles = Role::whereIsActive(1)->get();
                $roleList = [];

                foreach ($roles as $role) {
                    $roleList[] = [
                        'label' => $role->name,
                        'name'  => $role->id
                    ];
                }

                $schema['type'] = 'picklist';
                $schema['values'] = $roleList;
                $schema['description'] = 'Select a default role for users logging in with this SAML 2.0 service type.';
                break;
            case 'sp_nameIDFormat':
                $schema['label'] = 'SP NameIDFormat';
                $schema['default'] = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
                $schema['description'] =
                    'Specifies the constraints on the name identifier to be used to represent the requested subject.';
                break;
            case 'sp_x509cert':
                $schema['label'] = 'SP x509cert';
                $schema['description'] = 'Public x509 certificate of the Service Provider';
                break;
            case 'sp_privateKey':
                $schema['label'] = 'SP Private Key';
                $schema['description'] = 'Private Key of the Service Provider';
                break;
            case 'relay_state':
                $schema['label'] = 'Relay State';
                $schema['description'] = 'The URL to redirect to upon authenticating and returning from IdP. ' .
                    'Leaving this blank will output a JSON with authenticated user information including JWT. ' .
                    'If you like to include the JWT in a parameter to your Relay State URL, you can do so using ' .
                    'URL like http://example.com?jwt=_token_ . Here _token_ will be replaced by the actual JWT.';
                break;
            case 'idp_entityId':
                $schema['label'] = 'IdP EntityId';
                $schema['description'] = 'Identifier of the Identity Provider entity';
                break;
            case 'idp_singleSignOnService_url':
                $schema['label'] = 'IdP SSO service URL';
                $schema['description'] =
                    'URL Target of the Identity Provider where the Authentication Request Message will be sent.';
                break;
            case 'idp_x509cert':
                $schema['label'] = 'IdP x509cert';
                $schema['description'] = 'Public x509 certificate of the Identity Provider.';
                break;
            case 'icon_class':
                $schema['description'] = 'CSS Icon Class of the SAML 2.0 provider.';
                break;
        }
    }
}