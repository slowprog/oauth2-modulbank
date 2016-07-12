<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Modulbank extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Debug mode
     *
     * @var Api
     */
    protected $debug = false;

    /**
     * Api domain
     *
     * @var string
     */
    protected $domainApi = 'https://api.modulbank.ru/v1';

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domainApi.'/oauth/authorize';
    }

    /**
     * Get register url with data
     *
     * @return string
     */
    public function getRegistrationUrl($firstName, $lastName, $email, $cellPhone = null, $city = null, array $other = [])
    {
        $response = $this->sendRequest($this->getRequest(
            'POST',
            $this->domainApi.'/registration/setdata',
            [
                'body' => json_encode([
                    'clientId' => ($this->debug?'sandboxapp':$this->clientId),
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'cellPhone' => $cellPhone,
                    'city' => $city,
                    'other' => $other,
                ]),
            ]
        ));

        $result = json_decode((string)$response->getBody(), true);

        $params = [
            'code' => $result,
            'redirecturi' => $this->redirectUri
        ];

        if ($this->debug) {
            $params['sandbox'] = 'on';
        }

        return $this->appendQuery($this->domainApi.'/registration/setdata', $this->buildQueryString($params));
    }

    /**
     * Get access token url to retrieve token
     *
     * @param  array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domainApi.'/oauth/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->domainApi.'/account-info';
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return [];
    }

    /**
     * Check a provider response for errors.
     *
     * @link   https://developer.github.com/v3/#client-errors
     * @link   https://developer.github.com/v3/oauth/#common-errors-for-the-access-token-request
     * @throws IdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw ModulbankIdentityProviderException::clientException($response, $data);
        } elseif (isset($data['error'])) {
            throw ModulbankIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new ModulbankResourceOwner($response);
    }

    /**
     * Returns the default headers used by this provider.
     *
     * Typically this is used to set 'Accept' or 'Content-Type' headers.
     *
     * @return array
     */
    protected function getDefaultHeaders()
    {
        $headers = ['content-type' => 'application/json'];

        if ($this->debug) {
            $headers['sandbox'] = 'on';
        }

        return $headers;
    }
}
