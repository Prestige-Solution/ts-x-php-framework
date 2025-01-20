# TeamSpeak 3 PHP Framework (Rework)

### Current State: Experimental

Unfortunately, the original repo is no longer up to date and has not been maintained for 3 years. This is the reason why this project is being created.

The main goal is to bring the framework up to date and to equip it with extended unit tests which can also be carried out with a live server.

The ideal is that this version can be integrated into your own project and the main functionalities can be tested with your Teamspeak server.

### Current Milestones:
- [x] Collect initial inspection and understanding of the source code
- [x] Fix a few existing Unit Tests
- [ ] Rewrite source code up to PHP 8.2+
- [ ] Functionality tests of the framework
- [ ] Functionality tests for the bot identity
- [x] Minimalistic testing with a Live Server or Development Server
- [ ] Full Testing with a Live Server or Development Server
- [ ] Bug fixes
- [ ] Readme and Documentations

### New Test Developments
#### <u>Prepare your Environment</u>
Before you start, make sure that you have set the environment variables. You find [more information here](doc/testing-live-server.md)

#### <u>Servergroup Permissions for Query User</u>
I have been created a [Permission Ruleset](doc/query_user_servergroup_export.csv) which the Query User can use. With this Ruleset all Live Server Tests will be running.

To run all tests use `composer test`. 

**Note**: In the current state there are not all bugs are fixed

## Further information will follow
