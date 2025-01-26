# TeamSpeak 3 PHP Framework (Reworked)

Unfortunately, the original repo is no longer up to date and has not been maintained for 3 years. This is the reason why this project is being created.<br>
The main goal is to bring the framework up to date and to equip it with extended unit tests which can also be carried out with a live server.<br>
The ideal is that this version can be integrated into your own project and the main functionalities can be tested with your Teamspeak server.

### Current Milestones:
- [x] Collect initial inspection and understanding of the source code
- [x] Fix a few existing Unit Tests
- [ ] Rewrite source code up to PHP 8.2+
- [ ] Functionality tests of the framework
- [ ] Functionality tests for the bot identity
- [x] Minimalistic testing with a Live Server or Development Server
- [ ] Full Testing with a Live Server or Development Server
- [x] Bug fixes
- [ ] Readme and Documentations

---

### New Test Routine at Development
**<u>Prepare your Environment</u>**<br>
Before you start, make sure that you have set the environment variables. You find more information's at [testing-live-server](doc/testing-live-server.md)

**<u>Servergroup Permissions for Query User</u>**<br>
Use the [Permissions](doc/query_user_servergroup_export.csv) which the query user is used. All tests will be use these Permissions to validate the functionalities.

**<u>Run Tests</u>**<br>
To run all tests use `composer test`. <br>
**NOTE:** In the current state there are not all bugs are fixed.

--- 

## Further information will follow
