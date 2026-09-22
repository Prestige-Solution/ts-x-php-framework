# Testing with a Live or Development Server

## Environment Setup

```shell
cp .env.testing.example .env.testing
```

Replace all `DEV_LIVE_SERVER_*` variables with your TeamSpeak configuration:

| Environment Variable                                | Description                                                                                                   |
|-----------------------------------------------------|---------------------------------------------------------------------------------------------------------------|
| DEV_LIVE_SERVER_AVAILABLE=                          | Enable channel tests (default: `false`). When set to `false`, all channel tests are skipped.                  |
| DEV_LIVE_SERVER_HOST=                               | Your host address (recommended: IPv4).                                                                        |
| DEV_LIVE_SERVER_QUERY_PORT=                         | SSH ServerQuery port (default: `10022`).                                                                      |
| DEV_LIVE_SERVER_QUERY_USER=                         | Your ServerQuery username.                                                                                    |
| DEV_LIVE_SERVER_QUERY_USER_PASSWORD=                | Password for the ServerQuery user.                                                                            |
| DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=                  | Channel name for channel tests. Live server tests will create subchannels under this configured channel name. |
| DEV_LIVE_SERVER_UNIT_TEST_USER_ACTIVE=              | Enable user tests (default: `false`). When set to `false`, all user tests are skipped.                        |
| DEV_LIVE_SERVER_UNIT_TEST_USER=UnitTestUser         | Configure a TeamSpeak test client used for client tests.                                                      |
| DEV_LIVE_SERVER_UNIT_TEST_SIGNALS=                  | Enable signal tests (default: `false`). Note: This test suite has a long runtime.                             |
| DEV_LIVE_SERVER_UNIT_TEST_USER_EXTEND=UnitTestUser2 | Define a second test user.                                                                                    |
| DEV_LIVE_SERVER_UNIT_TEST_SERVER_PORT=              | Extended Unit Tests with additional server port                                                               |
| DEV_LIVE_SERVER_UNIT_TEST_SERVER_QUERY_LOGIN_NAME=  | Custom Bot Name                                                                                               |
| DEV_LIVE_SERVER_UNIT_TEST_SERVER_HOST_KEY=          | Yor Host Fingerprint                                                                                          |

### Important Configuration
* Set up your test server. You can use templates from [make-ts3-ssh-compatible](docker/make-ts3-ssh-compatible.md). Remember to create a new RSA host key on the TeamSpeak 3 server.
* Create a new channel named `UnitTest`.
* Rename the virtual server to `UnitTestServer`.

### Scenario 1: Use the serveradmin Query User (Recommended)
To test all functionality without permission issues, use the `serveradmin` query user.
When migrating from TeamSpeak Server 3 to 6, using `serveradmin` is required to avoid permission issues.
If you want to test both servers simultaneously, you can set the same `serveradmin` password on both servers.

### Scenario 2: Set Up a Custom ServerQuery Server Group
If you want to test using a specific bot identity, create a dedicated server group for the bot.
You can assign any desired permissions, but ensure the group has sufficient permission power. Otherwise, `insufficient_permissions` errors will occur.

### Important Notes
When running live server tests, clients may notice numerous anti-flood warnings in the server log.<br>
The test suite runs rapidly with frequent connections from the bot (establishing and closing a connection for each test).<br>
The `serveradmin` account bypasses anti-flood restrictions, so administrators might not see these warnings in their client view.
