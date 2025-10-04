# TeamSpeak X PHP Framework

The X stand for a non-specific Teamspeak Version. So we would handle all current and future Versions from a Teamspeak Server.

Unfortunately, the original repository is no longer up to date and has not been maintained for 3 years. This is the reason why this project is being created.<br>
The main goal is to bring the framework up to date and to equip it with extended unit tests which can also be carried out with tests for a live server.<br>
The ideal is that this version can be integrated into your own project and the main functionalities can be tested with your Teamspeak server.

---

# Installation
With the Refactoring at Version 3.0.0, the Framework has a few updates. But the most functionalities and namespaces are the same. 

**PHP Required Extensions**<br>
``apt install php8.3 php8.3-{common,mbstring,ssh2} -y``

**Via Composer**<br>
Current Version:<br>
``composer require prestige-solution/ts-x-php-framework``<br><br>
or with a specific release<br> 
``composer require prestige-solution/ts-x-php-framework:2.0.0-beta-2``

**Via Git**<br>
Add to your ``composer.json`` following options
```json
    "require": {
        "prestige-solution/ts-x-php-framework": "dev-main"
    },
    "repositories": [{
        "url": "https://github.com/Prestige-Solution/ts-x-php-framework.git",
        "type": "git"
    }],
```

---

# New test routines for future developments and improvements 
**<u>Prepare your Environment</u>**<br>
Before you start, make sure that you have set the environment variables. You find more information's at [testing-live-server](doc/testing-live-server.md)

**<u>Servergroup Permissions for Query User</u>**<br>
Use the [Permissions](doc/query_user_servergroup_export.csv) which the query user is used. All tests will be use these Permissions to validate the functionalities.

**<u>Run Tests</u>**<br>
To run all tests use `composer test`. <br>

--- 

# Build Factory URI
## Default URI Options
| Options  | Default Value |
|----------|---------------|
| timeout  | 10            |
| blocking | 0             |

If you build the serverquery without above parameters then there options will be set by default.<br>
**Note:** don't set timeout to 0. Further Information's at [php.net](https://www.php.net/manual/de/function.stream-select.php)

## Examples
- URI Example
```php
'serverquery://<user>:<pass>@<host>:<port>/?server_port=9987&no_query_clients=0&blocking=0&timeout=30&nickname=<bot_name>'
```

- Contains Username or Password special chars like ``+`` then you can use
```php
'serverquery://'.rawurlencode(<user>).':'.rawurlencode(<password>) .'@<host>:<port>/?server_port=9987&no_query_clients=0&blocking=0&timeout=30'
```
In my opinion you should **don't use specials chars**. Better, create a new QueryLogin Password and / or Username.

- You can use IPv4, IPv6 or DNS. Implement following Example in your Project.
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
