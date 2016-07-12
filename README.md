# Modulbank Provider for OAuth 2.0 Client

This package provides Modulbank OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require slowprog/oauth2-modulbank
```

## Usage

Usage is the same as The League's OAuth client, using `\League\OAuth2\Client\Provider\Modulbank` as the provider.

### Authorization Code Flow

```php
$provider = new \League\OAuth2\Client\Provider\Modulbank([
    'clientId'          => '{modulbank-client-id}',
    'clientSecret'      => '{modulbank-client-secret}',
    'redirectUri'       => 'https://example.com/callback-url',
    'debug'             => false
]);

// Get url for registration which must transfer to browser
// $url = $provider->getRegistrationUrl($firstName, $lastName, $email, $cellPhone);
// Or
// Get url for authorization which must transfer to browser
$url = $provider->getAuthorizationUrl();
$_SESSION['oauth2state'] = $provider->getState();
```

```php
if (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
} else {
    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try {

        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getNickname());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Github authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => [
        'account-info',
        'operation-history',
        'assistant-service',
        'money-transfer'
    ]
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```

If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://api.modulbank.ru/auth/#_5).

- account-info
- operation-history
- assistant-service

## License

The MIT License (MIT). Please see [License File](https://github.com/thephpleague/oauth2-github/blob/master/LICENSE) for more information.
