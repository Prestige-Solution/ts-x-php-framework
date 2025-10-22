# TeamSpeak X PHP Framework
[![PHP-CS-Fixer](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpcsfixer.yml/badge.svg?branch=main)](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpcsfixer.yml)
[![PHPUnit](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpunit.yml/badge.svg?branch=main)](https://github.com/Prestige-Solution/ts-x-php-framework/actions/workflows/phpunit.yml)
![Coverage](doc/coverage/coverage-badge.svg)
> [!CAUTION]
> **_IMPORTANT CHANGE_**<br>
> With Version 3.0.0 we refactored to integrate phpseclib3. This changes affects how TCP connections are established.
> The "raw" mode was removed, and the support for only ssh mode was established to handel Teamspeak 3 and Teamspeak 6 Server connections.


The X stands for a non-specific Teamspeak Server Version. So we would handle all current and future Versions from a Teamspeak Server.

Unfortunately, the original repository is no longer up to date and has not been maintained for 3 years. This is the reason why this project is being created. <br>
The main goal is to bring the framework up to date and to equip it with extended unit tests which can also be carried out with tests for a live server. <br>
The ideal is that this version can be integrated into your own project and the main functionalities can be tested with your Teamspeak server.

---

# Installation
With the Refactoring at Version 3.0.0, the Framework has a lot of changes. But most functionalities and namespaces are the same. 

**PHP Required Extensions**<br>
``apt install php8.3 php8.3-{common,mbstring,ssh2} -y``

**Via Composer**<br>
Current Version:<br>
``composer require prestige-solution/ts-x-php-framework``<br><br>
or with a specific release<br> 
``composer require prestige-solution/ts-x-php-framework:latest``<br>
``composer require prestige-solution/ts-x-php-framework:3.0.0-beta``<br><br>
or with a specific branch<br>
``composer require prestige-solution/ts-x-php-framework:dev-ts-x-refactoring-dev``

---

## If your teamspeak 3 server is not running with version 3.x, check the rsa host key situation
Check out the documentation [make-ts3-ssh-compatible.md](doc/docker/make-ts3-ssh-compatible.md)<br>
There you can find instructions to set up a compatible rsa host key. It should work with docker and non-docker versions.

# New test routines for future developments and improvements with live server testing
**<u>Prepare your Environment</u>**<br>
Before you start UnitTests, make sure that you have set the environment variables. You find more information's at [testing-live-server](doc/testing-live-server.md)

**<u>Permissions for Query User</u>**<br>
The best way to test all functionalities is to use the serveradmin query user. <br>
The serveradmin is != Server Admin there you can find in your Teamspeak Client UI. <br>

| serveradmin (Query)                 | Server Admin (GUI)                 |
|-------------------------------------|------------------------------------|
| Max. permission value: 100 (=grant) | Max. permission value: 75 (=grant) |

You can find more information in the Documentation [testing-live-server](doc/testing-live-server.md)

**<u>Additional Node</u>** <br>
- We know the serveradmin (query user) is a high-security risk if you use it over the internet. We would try to find a better solution with SSH public key authentication. <br>
- Currently, you can improve fail2ban, query_ip_whitelist and query_ip_blacklist.

**<u>Run Tests</u>**<br>
To run all tests use `composer test`. <br>

--- 

# Build Factory URI
## Default URI Options
| Options  | Default Value |
|----------|---------------|
| timeout  | 10            |

If you build the serverquery without the above parameter, then their options will be set by default. <br>
**Note:** don't set timeout to 0. Further Information's at [php.net](https://www.php.net/manual/de/function.stream-select.php)

## Examples
- URI Example
```php
'serverquery://<user>:<pass>@<host>:<port>/?server_port=9987&no_query_clients=0&timeout=30&nickname=<bot_name>'
```

- Contains Username or Password special chars like ``+`` then you can use
```php
'serverquery://'.rawurlencode(<user>).':'.rawurlencode(<password>) .'@<host>:<port>/?server_port=9987&no_query_clients=0&timeout=30'
```
In my opinion, you should **don't use specials chars**. Better, create a new QueryLogin Password and / or Username.

- You can use IPv4, IPv6 or DNS. Implement the following Example in your Project.
```php
if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $validatedHost = $host;
} elseif (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || filter_var(gethostbyname($host), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $validatedHost = '['.$host.']';
} else {
    return false;
}
```

---

# Important note
We have no intention of abandoning the original repository altogether. We will keep the namespace so that an update to the original PlanetTeamspeak repository can be considered as far as possible and the Support from the Maintainer is back.
