<?php

namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\ModulbankAccessToken;
use League\OAuth2\Client\Grant\AbstractGrant;

class Modulbank extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Debug mode
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Token
     *
     * @var string
     */
    protected $token;

    /**
     * Api domain
     *
     * @var string
     */
    protected $domainApi = 'https://api.modulbank.ru/v1';

    /**
     * Constructs an OAuth 2.0 service provider.
     *
     * @param array $options An array of options to set on this provider.
     *     Options include `clientId`, `clientSecret`, `redirectUri`, and `state`.
     *     Individual providers may introduce more options, as needed.
     * @param array $collaborators An array of collaborators that may be used to
     *     override this provider's default behavior. Collaborators include
     *     `grantFactory`, `requestFactory`, `httpClient`, and `randomFactory`.
     *     Individual providers may introduce more collaborators, as needed.
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct($options, $collaborators);

        if ($this->token) {
            $this->token = new ModulbankAccessToken([
                'accessToken' => $this->token
            ]);
        }
    }

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
     * Get authorization url with debug
     *
     * @return string
     */
    public function getAuthorizationUrlShort()
    {
        $base = $this->getBaseAuthorizationUrl();

        return $this->debug?$this->appendQuery($base, 'sandbox=on'):$base;
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
                    'redirectUri' => $this->redirectUri,
                ]),
            ]
        ));

        $result = json_decode((string)$response->getBody(), true);

        $params = [
            'code' => $result,
        ];

        if ($this->debug) {
            $params['sandbox'] = 'on';
            $params['redirecturi'] = $this->redirectUri;
        }

        return $this->appendQuery($this->domainApi.'/registration/register', $this->buildQueryString($params));
    }

    /**
     * Get authorization params.
     *
     * @param  array $params
     * @return array
     */
    public function getAuthorizationParams(array $params = [])
    {
        return $this->getAuthorizationParameters($params);
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
     * Get account info
     *
     * @return array
     * @permission account-info
     */
    public function getAccountInfo()
    {
        return json_decode((string)$this->sendRequest($this->getAuthenticatedRequest(
            'POST',
            $this->domainApi.'/account-info',
            $this->token
        ))->getBody(), true);
    }

    /**
     * Get operation history
     *
     * @param string $bankAccountId
     * @param string $category
     * @param integer $records
     * @param string $from
     * @param string $till
     * @return array
     * @permission operation-history
     */
    public function getOperationHistory($bankAccountId, $category = null, $records = null, $from = null, $till = null)
    {
        $params = [];

        if ($category) {
            $params['category'] = $category;
        }

        if ($records) {
            $params['records'] = $records;
        }

        if ($from) {
            $params['from'] = $from;
        }

        if ($till) {
            $params['till'] = $till;
        }

        return json_decode((string)$this->sendRequest($this->getAuthenticatedRequest(
            'POST',
            $this->domainApi.'/operation-history/'.$bankAccountId,
            $this->token,
            ['body' => json_encode($params)]
        ))->getBody(), true);
    }

    /**
     * Get balance on account
     *
     * @param string $bankAccountId
     * @return float
     * @permission account-info
     */
    public function getBalance($bankAccountId)
    {
        return json_decode((string)$this->sendRequest($this->getAuthenticatedRequest(
            'POST',
            $this->domainApi.'/account-info/balance/'.$bankAccountId,
            $this->token
        ))->getBody(), true);
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
     * Returns the string that should be used to separate scopes when building
     * the URL for requesting an access token.
     *
     * @return string Scope separator, defaults to ','
     */
    protected function getScopeSeparator()
    {
        return ' ';
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

    /**
     * Requests an access token using a specified grant and option set.
     *
     * @param  mixed $grant
     * @param  array $options
     * @return AccessToken
     */
    public function getAccessToken($grant, array $options = [])
    {
        $grant = $this->verifyGrant($grant);

        $params = [
            'clientId'     => ($this->debug?'sandboxapp':$this->clientId),
            'clientSecret' => ($this->debug?'sandboxappsecret':$this->clientSecret),
            'redirectUri'  => $this->redirectUri,
        ];

        if ($this->debug) {
            $headers['sandbox'] = 'on';
        }

        $params = array_merge($params, $options);
        // $params   = $grant->prepareRequestParameters($params, $options);

        $request  = $this->getAccessTokenRequest($params);
        $response = $this->getResponse($request);
        $prepared = $this->prepareAccessTokenResponse($response);
        $token    = $this->createAccessToken($prepared, $grant);

        return $token;
    }

    /**
     * Returns authorization parameters based on provided options.
     *
     * @param  array $options
     * @return array Authorization parameters
     */
    protected function getAuthorizationParameters(array $options)
    {
        if (empty($options['state'])) {
            $options['state'] = $this->getRandomState();
        }

        if (empty($options['scope'])) {
            $options['scope'] = $this->getDefaultScopes();
        }

        $options += [
            'responseType'   => 'code',
            'approvalPrompt' => 'auto'
        ];

        if (is_array($options['scope'])) {
            $separator = $this->getScopeSeparator();
            $options['scope'] = implode($separator, $options['scope']);
        }

        // Store the state as it may need to be accessed later on.
        $this->state = $options['state'];

        $options['clientId']    = ($this->debug?'sandboxapp':$this->clientId);
        $options['redirectUri'] = $this->redirectUri;
        $options['state']       = $this->state;

        return $options;
    }

    /**
     * Builds request options used for requesting an access token.
     *
     * @param  array $params
     * @return array
     */
    protected function getAccessTokenOptions(array $params)
    {
        // $options = ['headers' => ['content-type' => 'application/x-www-form-urlencoded']];

        if ($this->getAccessTokenMethod() === self::METHOD_POST) {
            $options['body'] = $this->getAccessTokenBody($params);
        }

        return $options;
    }

    /**
     * Returns the request body for requesting an access token.
     *
     * @param  array $params
     * @return string
     */
    protected function getAccessTokenBody(array $params)
    {
        return json_encode($params);
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param  array $response
     * @param  AbstractGrant $grant
     * @return AccessToken
     */
    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new ModulbankAccessToken($response);
    }
}
