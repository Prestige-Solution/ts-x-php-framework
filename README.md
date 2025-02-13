# TeamSpeak 3 PHP Framework (Reworked)

Unfortunately, the original repo is no longer up to date and has not been maintained for 3 years. This is the reason why this project is being created.<br>
The main goal is to bring the framework up to date and to equip it with extended unit tests which can also be carried out with a live server.<br>
The ideal is that this version can be integrated into your own project and the main functionalities can be tested with your Teamspeak server.

### Current Milestones:
- [x] Collect initial inspection and understanding of the source code
- [x] Fix a few existing Unit Tests
- [x] Rewrite source code up to PHP 8.2+
- [x] Functionality tests of the framework
- [x] Functionality tests for the bot identity
- [x] Minimalistic testing with a Live Server or Development Server
- [x] Bug fixes
- [ ] Search Bugs and fixes
- [ ] Full Testing with a Live Server or Development Server
- [ ] Readme and Documentations

---

### New Test Routine at Development
**<u>Prepare your Environment</u>**<br>
Before you start, make sure that you have set the environment variables. You find more information's at [testing-live-server](doc/testing-live-server.md)

**<u>Servergroup Permissions for Query User</u>**<br>
Use the [Permissions](doc/query_user_servergroup_export.csv) which the query user is used. All tests will be use these Permissions to validate the functionalities.

**<u>Run Tests</u>**<br>
To run all tests use `composer test`. <br>

--- 

## Build Factory URI
### Default URI Options
| Options  | Default Value |
|----------|---------------|
| timeout  | 10            |
| blocking | 0             |
| tls      | 0             |
| ssh      | 0             |

If you build the serverquery without above parameters then there options will be set by default. 
**Note:** don't set timeout to 0. Further Information's at [php.net](https://www.php.net/manual/de/function.stream-select.php)

### Examples
RAW Mode (stream_socket_client)
```php
'serverquery://<user>:<password>@<host>:<query_port>/?server_port=<server_port>&ssh=0&no_query_clients=0&blocking=0&timeout=30&nickname=<bot_name>'
```

SSH Mode (ssh2_shell)
```php
'serverquery://<user>:<password>@<host>:<query_port>/?server_port=<server_port>&ssh=1&no_query_clients=0&blocking=0&timeout=30&nickname=<bot_name>'
```

Contains Username or Password special chars like ``+`` then you can use
```php
'serverquery://' . rawurlencode(<user>) . ':' . rawurlencode(<password>) .' @<host>:<query_port>/?server_port=<server_port>&ssh=1&no_query_clients=0&blocking=0&timeout=30&nickname=<bot_name>'
```
In my opinion you should **don't use specials chars**. Better, create a new QueryLogin Password and / or Username.
