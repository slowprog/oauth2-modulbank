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

// Get url for registration which must transfer to browser (send GET)
// $url = $provider->getRegistrationUrl($firstName, $lastName, $email, $cellPhone);
// Or
// Get url for authorization which must transfer to browser (send POST)
$url = $provider->getAuthorizationUrlShort();
$params = $provider->getAuthorizationParams([
    'scope' => 'account-info operation-history assistant-service money-transfer',
]);
```

Callback file:

```php
$provider = new \League\OAuth2\Client\Provider\Modulbank([
    'clientId'          => '{modulbank-client-id}',
    'clientSecret'      => '{modulbank-client-secret}',
    'debug'             => false
]);

$token = $provider->getAccessToken('authorization_code', [
    'code' => $code
]);

// Use this to interact with an API on the users behalf
echo $token->getToken();
```

### Call methods

```php
$provider = new \League\OAuth2\Client\Provider\Modulbank([
    'clientId'          => '{modulbank-client-id}',
    'clientSecret'      => '{modulbank-client-secret}',
    'token'             => '{modulbank-client-token}',
    'debug'             => false
]);

$info = $provider->getAccountInfo();
// $history = $provider->getOperationHistory('9f65fff4-d638-41d8-83eb-a616039d3fe5');
// $balance = $provider->getBalance('9f65fff4-d638-41d8-83eb-a616039d3fe5');
```

### Managing Scopes

When creating your Modulbank authorization URL, you can specify the state and scopes your application may authorize.

```php
$params = $provider->getAuthorizationParams([
    'scope' => 'assistant-service money-transfer',
]);
```

If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the [following scopes are available](https://api.modulbank.ru/auth/#_5).

- account-info
- operation-history
- assistant-service
- money-transfer

## License

The MIT License (MIT). Please see [License File](https://github.com/SlowProg/oauth2-modulbank/blob/master/LICENSE) for more information.
