<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
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
        $this->ensureSAMLResponse();
        /** @var SAML $service */
        $service = $this->getParent();
        $auth = $service->getAuth();
        $auth->processResponse();
        $errors = $auth->getErrors();

        if (!empty($errors)) {
            throw new InternalServerErrorException('Bad response received from idp. ' . implode(', ', $errors));
        }

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
        $response['SAMLResponse'] = array_get($_POST, 'SAMLResponse');

        return $response;
    }

    protected function ensureSAMLResponse()
    {
        if (!isset($_POST) || !isset($_POST['SAMLResponse'])) {
            $sr = $this->request->getPayloadData();
            if (!isset($sr['SAMLResponse'])) {
                throw new BadRequestException('Invalid SAML Response provided');
            }
            $_POST = $sr;
        }
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
            $userData = [
                'username'   => $email,
                'name'       => 'SAML User',
                'first_name' => 'SAML',
                'last_name'  => 'USER',
                'email'      => $email,
                'is_active'  => true,
                'saml'       => SAML::PROVIDER_NAME,
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
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $class = trim(strrchr(static::class, '\\'), '\\');

        $base = [
            $path => [
                'post' => [
                    'summary'     => 'process' . $capitalized . $class . 'IdPResponse() - Process IdP response',
                    'operationId' => 'process' . $capitalized . $class . 'IdPResponse',
                    'description' => 'Processes XML IdP response, creates DreamFactory shadow user as needed, establishes sessions, returns JWT or redirects to RelayState.',
                    'requestBody' => [
                        'description' => 'SAML Request.',
                        'schema'      => [
                            'type'       => 'object',
                            'properties' => [
                                'IdPResponse' => [
                                    'type'        => 'string',
                                    'required'    => true,
                                    'description' => 'The XML IdP response.'
                                ],
                                'relay_state'  => [
                                    'type'        => 'string',
                                    'description' => 'Value of the current relay state.'
                                ]
                            ]
                        ],
                    ],
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                            'content'     => [
                                'application/json' => [
                                    'schema' => [
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
                                ]
                            ]
                        ],
                        '302' => [
                            'description' => 'Redirect to RelayState',
                        ],
                    ],
                ],
            ],
        ];

        return $base;
    }
}