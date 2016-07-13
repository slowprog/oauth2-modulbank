<?php
/**
 * This file is part of the league/oauth2-client library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Alex Bilbie <hello@alexbilbie.com>
 * @license http://opensource.org/licenses/MIT MIT
 * @link http://thephpleague.com/oauth2-client/ Documentation
 * @link https://packagist.org/packages/league/oauth2-client Packagist
 * @link https://github.com/thephpleague/oauth2-client GitHub
 */

namespace League\OAuth2\Client\Token;

use InvalidArgumentException;
use JsonSerializable;
use RuntimeException;

/**
 * Represents an access token.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-1.4 Access Token (RFC 6749, §1.4)
 */
class ModulbankAccessToken extends AccessToken
{
    /**
     * Constructs an access token.
     *
     * @param array $options An array of options returned by the service provider
     *     in the access token request. The `access_token` option is required.
     * @throws InvalidArgumentException if `access_token` is not provided in `$options`.
     */
    public function __construct(array $options = [])
    {
        if (empty($options['accessToken'])) {
            throw new InvalidArgumentException('Required option not passed: "access_token"');
        }

        $this->accessToken = $options['accessToken'];

        if (!empty($options['resourceOwnerId'])) {
            $this->resourceOwnerId = $options['resourceOwnerId'];
        }

        if (!empty($options['refreshToken'])) {
            $this->refreshToken = $options['refreshToken'];
        }

        // We need to know when the token expires. Show preference to
        // 'expires_in' since it is defined in RFC6749 Section 5.1.
        // Defer to 'expires' if it is provided instead.
        if (!empty($options['expiresIn'])) {
            $this->expires = time() + ((int) $options['expiresIn']);
        } elseif (!empty($options['expires'])) {
            // Some providers supply the seconds until expiration rather than
            // the exact timestamp. Take a best guess at which we received.
            $expires = $options['expires'];

            if (!$this->isExpirationTimestamp($expires)) {
                $expires += time();
            }

            $this->expires = $expires;
        }

        // Capure any additional values that might exist in the token but are
        // not part of the standard response. Vendors will sometimes pass
        // additional user data this way.
        $this->values = array_diff_key($options, array_flip([
            'accessToken',
            'resourceOwnerId',
            'refreshToken',
            'expiresIn',
            'expires',
        ]));
    }

    /**
     * Returns an array of parameters to serialize when this is serialized with
     * json_encode().
     *
     * @return array
     */
    public function jsonSerialize()
    {
        $parameters = $this->values;

        if ($this->accessToken) {
            $parameters['accessToken'] = $this->accessToken;
        }

        if ($this->refreshToken) {
            $parameters['refreshToken'] = $this->refreshToken;
        }

        if ($this->expires) {
            $parameters['expires'] = $this->expires;
        }

        if ($this->resourceOwnerId) {
            $parameters['resourceOwnerId'] = $this->resourceOwnerId;
        }

        return $parameters;
    }
}
