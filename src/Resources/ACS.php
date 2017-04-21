<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Resources\System\Environment;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use Carbon\Carbon;

class ACS extends BaseSamlResource
{
    const RESOURCE_NAME = 'acs';

    /**
     * {@inheritdoc}
     */
    protected function handlePOST()
    {
        /** @var SAML $service */
        $service = $this->getParent();
        $auth = $service->getAuth();
        $auth->processResponse();
        $errors = $auth->getErrors();

        if (!empty($errors)) {
            throw new InternalServerErrorException('Invalid ASC response received. ' . implode(', ', $errors));
        }

        //$attributes = $auth->getAttributes();
        //$nameIdFormat = $auth->getNameIdFormat();
        $nameId = $auth->getNameId();

        if (!$this->isEmail($nameId)) {
            $nameId = $nameId . '+' . $service->getName() . '@' . $service->getName() . '.com';
        } else {
            list($emailId, $domain) = explode('@', $nameId);
            $nameId = $emailId . '+' . $service->getName() . '@' . $domain;
        }

        $dfUser = $this->createShadowSamlUser($nameId);
        $dfUser->last_login_date = Carbon::now()->toDateTimeString();
        $dfUser->confirm_code = null;
        $dfUser->save();

        Session::setUserInfoWithJWT($dfUser);
        $response = Session::getPublicInfo();
        $relayState = $this->request->getPayloadData('RelayState');
        $ssoUrl = Environment::getURI() . '/api/v2/' . $this->getParent()->getName() . '/sso';

        if (!empty($relayState) && rtrim($relayState, '/') !== $ssoUrl) {
            $relayState = str_replace('_token_', array_get($response, 'session_token'), $relayState);

            return redirect()->to($relayState);
        }

        return $response;
    }

    /**
     * Creates SAML shadow user in DF
     *
     * @param $email
     *
     * @return User
     */
    protected function createShadowSamlUser($email)
    {
        $user = User::whereEmail($email)->first();

        if (empty($user)) {
            $serviceName = $this->getParent()->getName();
            $userData = [
                'username'   => $email,
                'name'       => 'SAML User',
                'first_name' => 'SAML',
                'last_name'  => 'USER',
                'email'      => $email,
                'is_active'  => true,
                'saml'       => $serviceName,
            ];

            $user = User::create($userData);
        }

        if (!empty($defaultRole = $this->getParent()->getDefaultRole())) {
            User::applyDefaultUserAppRole($user, $defaultRole);
        }
        if (!empty($serviceId = $this->getServiceId())) {
            User::applyAppRoleMapByService($user, $serviceId);
        }

        return $user;
    }

    /**
     * Checks to see if a string is a valid email address.
     *
     * @param $string
     *
     * @return bool
     */
    private function isEmail($string)
    {
        $data = ['name' => $string];
        $rule = ['name' => 'email'];

        $v = \Validator::make($data, $rule);

        if ($v->fails()) {
            return false;
        } else {
            return true;
        }
    }

    /** {@inheritdoc} */
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        unset($base['paths'][$path]['get']);

        $base['paths'][$path]['post'] = [
            'tags'        => [$serviceName],
            'summary'     => 'processIdPResponse() - Process IdP response',
            'operationId' => 'processResponse',
            'consumes'    => ['application/xml'],
            'produces'    => ['application/json', 'application/xml', 'text/csv', 'text/plain'],
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                    'schema'      => [
                        'type'       => 'object',
                        'properties' => [
                            'session_token'   => [
                                'type' => 'string'
                            ],
                            'session_id'      => [
                                'type' => 'string'
                            ],
                            'id'              => [
                                'type' => 'integer'
                            ],
                            'name'            => [
                                'type' => 'string'
                            ],
                            'first_name'      => [
                                'type' => 'string'
                            ],
                            'last_name'       => [
                                'type' => 'string'
                            ],
                            'email'           => [
                                'type' => 'string'
                            ],
                            'is_sys_admin'    => [
                                'type' => 'boolean'
                            ],
                            'last_login_date' => [
                                'type' => 'string'
                            ],
                            'host'            => [
                                'type' => 'string'
                            ]
                        ]
                    ]
                ],
                '302'     => [
                    'description' => 'Redirect to RelayState',
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
            'description' => 'Processes XML IdP response, creates DreamFactory shadow user as needed, establishes sessions, returns JWT or redirects to RelayState.'
        ];

        return $base;
    }
}