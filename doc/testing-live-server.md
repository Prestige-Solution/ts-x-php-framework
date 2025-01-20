Testing Live Server
==================
## Setup Environment

```shell
cp .env.testing.example .env.testing
```

Replace all `DEV_LIVE_SERVER_*` Variables with your TS3 Server Config

| DEV_LIVE_SERVER_AVAILABLE=           | true enabled the test, false skipped the test                                                   |
|--------------------------------------|-------------------------------------------------------------------------------------------------|
| DEV_LIVE_SERVER_AVAILABLE=           | Runs the Live Server Tests (Default = false)                                                    |
| DEV_LIVE_SERVER_HOST=                | Your IP or Host Address                                                                         |
| DEV_LIVE_SERVER_QUERY_PORT=          | Raw = 10011 / ssh = 10022                                                                       |
| DEV_LIVE_SERVER_QUERY_USER=          | Your Query Username                                                                             |
| DEV_LIVE_SERVER_QUERY_USER_PASSWORD= | Password for the Query User                                                                     |
| DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=   | Set a Channelname for the Tests. Live Server Tests will create channels under this channel name |

## IMPORTANT
Set your ```DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=``` Change and Delete Power Permission to 75`otherwise the test with permissions will be fail.
