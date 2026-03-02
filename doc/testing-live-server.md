Testing with Live or Development Server
==================
## Setup Environment

```shell
cp .env.testing.example .env.testing
```
Replace all `DEV_LIVE_SERVER_*` Variables with your Teamspeak Configuration

| Environment Variable                                | Description                                                                                                          |
|-----------------------------------------------------|----------------------------------------------------------------------------------------------------------------------|
| DEV_LIVE_SERVER_AVAILABLE=                          | Activate Channel Tests (Default =  false). At false all Channel Tests will be skipped                                |
| DEV_LIVE_SERVER_HOST=                               | Your Host Address (Recommended: IPv4)                                                                                |
| DEV_LIVE_SERVER_QUERY_PORT=                         | ssh = 10022                                                                                                          |
| DEV_LIVE_SERVER_QUERY_USER=                         | Your Query Username                                                                                                  |
| DEV_LIVE_SERVER_QUERY_USER_PASSWORD=                | Password for the Query User                                                                                          |
| DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=                  | Setup a Channelname for Channel Tests. The Live Server Tests will create channels under this configured Channelname* |
| DEV_LIVE_SERVER_UNIT_TEST_USER_ACTIVE=              | Activate User Tests (Default = false). At false all User Tests will be skipped                                       |
| DEV_LIVE_SERVER_UNIT_TEST_USER=UnitTestUser         | Setup a Teamspeak Testclient. It will be use for Client Tests                                                        |
| DEV_LIVE_SERVER_UNIT_TEST_SIGNALS=                  | Test Signals. Default = false. NOTE: This Test has a very long Test duration.                                        |
| DEV_LIVE_SERVER_UNIT_TEST_USER_EXTEND=UnitTestUser2 | Define a second TestUser                                                                                             |

### Important Configuration
* Setup your Testserver. You can use Templates from ![make-ts3-ssh-compatible](../doc/docker/make-ts3-ssh-compatible.md). Remember to create a new RSA Hostkey at Teamspeak 3 Server.
* Create a new Channel with Channelname ``UnitTest``
* Rename the Server Name to ``UnitTestServer``

### Scenario 1: Use the serveradmin query (RECOMMENDED)
If you want to test all functions without permissions issues, you should use the serveradmin query.
When you want to migrate from Teamspeak Server 3 to 6, you need the serveradmin query, otherwise you run in permission issues.
If you want to test both Servers at the same time, you can set the same serveradmin password for both Servers.

### Scenario 2: Set up an individual query Servergroup
If you want to test or use a specific Bot Identity, you have to create a new Servergroup for the bot.
You can define all permissions there you want but be sure the permissions have enough power. Otherwise, you get insufficient_permissions errors.

### Important Notes
If you run these Live Server Tests, the Clients see massive Server Log entrys with Anti-Flood errors. <br>
The test is rapid with a lot of connections from the bot (one connection and disconnection for each test). <br>
The Serveradministrator has no Anti-Flood Permissions, so maybe you as an Administrator can't see these entrys.
