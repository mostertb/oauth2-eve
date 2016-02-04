# EVE Online Provider for OAuth 2.0 Client
[![Source Code](http://img.shields.io/badge/source-killmails/oauth2--eve-blue.svg?style=flat-square)](https://github.com/killmails/oauth2-eve)
[![Packagist Version](https://img.shields.io/packagist/v/killmails/oauth2-eve.svg?style=flat-square)](https://packagist.org/packages/killmails/oauth2-eve)
[![Build Status](https://img.shields.io/travis/killmails/oauth2-eve/master.svg?style=flat-square)](https://travis-ci.org/killmails/oauth2-eve)
[![HHVM Status](https://img.shields.io/hhvm/killmails/oauth2-eve.svg?style=flat-square)](http://hhvm.h4cc.de/package/killmails/oauth2-eve)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/killmails/oauth2-eve.svg?style=flat-square)](https://scrutinizer-ci.com/g/killmails/oauth2-eve/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/killmails/oauth2-eve.svg?style=flat-square)](https://scrutinizer-ci.com/g/killmails/oauth2-eve)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/killmails/oauth2-eve.svg?style=flat-square)](https://packagist.org/packages/killmails/oauth2-eve)

EVE Online Provider for the OAuth 2.0 Client

This package provides [EVE Online](https://developers.eveonline.com) OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/killmails/oauth2-eve).

## Installation

To install, use composer:

```
composer require killmails/oauth2-eve
```

## Usage

Usage is the same as The League's OAuth client, using `\Killmails\OAuth2\Client\Provider\EveOnline` as the provider.

### Authorization Code Flow

```php
$provider = new Killmails\OAuth2\Client\Provider\EveOnline([
    'clientId'          => '{eve-client-id}',
    'clientSecret'      => '{eve-client-key}',
    'redirectUri'       => 'https://example.com/callback-url',
]);

if (!isset($_GET['code'])) {

    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: ' . $authUrl);
    exit;

// Check given state against previously stored one to mitigate CSRF attack
} elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

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
        printf('Hello %s!', $user->getCharacterName());

    } catch (Exception $e) {

        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your EVE Online authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['characterFittingsRead','characterFittingsWrite'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the following scopes are available.

- characterContactsRead
- characterContactsWrite
- characterFittingsRead
- characterFittingsWrite
- characterLocationRead
- characterNavigationWrite
- publicData

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please see [CONTRIBUTING](https://github.com/killmails/oauth2-eve/blob/master/CONTRIBUTING.md) for details.


## Credits

- [Alex Soban](https://github.com/SobanVuex)
- [All Contributors](https://github.com/killmails/oauth2-eve/contributors)


## License

The MIT License (MIT). Please see [License File](https://github.com/killmails/oauth2-eve/blob/master/LICENSE) for more information.
