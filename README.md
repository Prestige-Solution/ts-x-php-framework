# TeamSpeak X PHP Framework
[![PHP-CS-Fixer](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpcsfixer.yml/badge.svg?branch=main)](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpcsfixer.yml)
[![PHPUnit](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpunit.yml/badge.svg?branch=main)](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpunit.yml)
![Coverage](doc/coverage/coverage-badge.svg)

> [!CAUTION]
> **_IMPORTANT CHANGE_**<br>
> Starting with Version 3.x, the framework has been refactored to integrate `phpseclib3`. These changes affect how TCP connections are established.
> The "raw" mode has been removed, and only SSH mode is supported to handle TeamSpeak 3 and TeamSpeak 6 Server API connections.

The "X" stands for a non-version-specific TeamSpeak server implementation, allowing support for all current and future versions of TeamSpeak Server.

As the original repository is no longer maintained, this project was created to bring the framework up to date, ensure PHP 8.x compatibility, and provide extensive unit test suites that can also be executed against a live server.

---

# Installation
With the refactoring in version 3.x, the framework underwent substantial internal changes, while preserving most functionality and public namespaces.

**Required PHP Extensions**<br>
`apt install php8.3 php8.3-{common,mbstring,ssh2} -y`

**Via Composer**<br>
Current Version:<br>
`composer require prestige-solution/ts-x-php-framework`

Or with a specific release:<br>
`composer require prestige-solution/ts-x-php-framework:latest`<br>
`composer require prestige-solution/ts-x-php-framework:3.0.0-beta`

Or with a specific branch:<br>
`composer require prestige-solution/ts-x-php-framework:dev-ts-x-refactoring-dev`

---

## If your TeamSpeak 3 server is not running with version 3.x, check the RSA host key configuration
See [make-ts3-ssh-compatible.md](doc/docker/make-ts3-ssh-compatible.md) for instructions on setting up a compatible RSA host key. It supports both Docker and non-Docker setups.

# Test Routines for Live Server Testing
**<u>Prepare your Environment</u>**<br>
Before running unit tests, ensure that all required environment variables are set. For more details, see [testing-live-server](doc/testing-live-server.md).

**<u>Permissions for Query User</u>**<br>
The recommended way to test all functionality is using the `serveradmin` query user.<br>
Note: `serveradmin` is distinct from the Server Admin group found in the TeamSpeak client UI:

| serveradmin (Query)                 | Server Admin (GUI)                 |
|-------------------------------------|------------------------------------|
| Max. permission value: 100 (=grant) | Max. permission value: 75 (=grant) |

For further details, see [testing-live-server](doc/testing-live-server.md).

**<u>Additional Notes</u>** <br>
- Using `serveradmin` credentials over public networks poses a security risk. Support for SSH public key authentication is planned.
- In the meantime, protect your instance using `fail2ban`, `query_ip_whitelist`, and `query_ip_blacklist`.

**<u>Run Tests</u>**<br>
To run all tests, execute `composer test`.

---

# Build Factory URI
## Default URI Options
| Option   | Default Value |
|----------|---------------|
| timeout  | 10            |

If you build the ServerQuery connection without specifying the parameter above, default values will be applied.<br>
**Note:** Do not set `timeout` to 0. For more information, see [php.net stream_select](https://www.php.net/manual/de/function.stream-select.php).

## Examples
- URI Example:
```php
'serverquery://<user>:<pass>@<host>:<port>/?server_port=9987&no_query_clients=0&timeout=30&nickname=<bot_name>'
```

- Verify your host with a fingerprint:
```php
'serverquery://<user>:<pass>@<host>:<port>/?server_port=9987&no_query_clients=0&timeout=30&nickname=<bot_name>&fingerprint=<fingerprint>'
```

- If your username or password contains special characters such as `+`, use `rawurlencode()`:
```php
'serverquery://' . rawurlencode($user) . ':' . rawurlencode($password) . '@<host>:<port>/?server_port=9987&no_query_clients=0&timeout=30'
```
*Recommendation:* Avoid special characters in ServerQuery passwords and usernames where possible by generating plain alphanumeric credentials.

- Support IPv4, IPv6, or hostnames:
```php
if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $validatedHost = $host;
} elseif (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $validatedHost = '[' . $host . ']';
} else {
    return false;
}
```

---

# Important Note
We have no intention of abandoning compatibility with the original upstream repository. The namespace is preserved so that integration with the original PlanetTeamspeak repository remains possible if upstream maintenance resumes.
