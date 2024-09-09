<?php

namespace DreamFactory\Core\Saml\Resources;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Saml\Services\SAML;
use DreamFactory\Core\Utility\ResponseFactory;
use DreamFactory\Core\Utility\Session as SessionUtilities;

class Metadata extends BaseSamlResource
{
    const RESOURCE_NAME = 'metadata';

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        // Check if the user is authenticated
        if (!SessionUtilities::isAuthenticated()) {
            // Return a JSON response with the appropriate headers
            return ResponseFactory::create(
                [
                    'error' => [
                        'code' => 400,
                        'message' => "No session token (JWT) provided. Please provide a valid JWT using X-DreamFactory-Session-Token request header or 'session_token' url query parameter."
                    ]
                ],
                'application/json', // Set content-type to JSON
                400 // HTTP status code
            );
        }
        /** @var SAML $service */
        $service = $this->getParent();
        $settings = $service->getAuth()->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (!empty($errors)) {
            throw new InternalServerErrorException('Invalid SP metadata. ' . implode(', ', $errors));
        }

        return ResponseFactory::create($metadata, 'text/xml');
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $service = $this->getServiceName();
        $capitalized = camelize($service);

        $base = [
            $path => [
                'get' => [
                    'summary'     => 'Gets SAML 2.0 metadata',
                    'description' => 'Generates SAML 2.0 XML metadata.',
                    'operationId' => 'get' . $capitalized . 'Metadata',
                    'responses'   => [
                        '200' => [
                            'description' => 'Success',
                        ],
                    ],
                ],
            ],
        ];

        return $base;
    }
}
