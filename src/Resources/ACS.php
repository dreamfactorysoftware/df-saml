<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Resources\System\Environment;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Utility\Session;
use DreamFactory\Core\Models\User;
use Carbon\Carbon;

class ACS extends BaseRestResource
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

}