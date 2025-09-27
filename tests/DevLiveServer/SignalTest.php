<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TeamSpeak3Exception;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Node;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class SignalTest extends TestCase
{
    /**
     * ATTENTION
     * Use the .env.testing Variable "DEV_LIVE_SERVER_AVAILABLE" to activate this Test
     * Use this Testcase only with a development Teamspeak Server
     * Otherwise the TS3 Server can be destroyed
     */
    private string $active;

    private string $host;

    private string $queryPort;

    private string $user;

    private string $password;

    private string $ts3_server_uri;

    private string $ts3_unit_test_signals;

    private int $duration;

    private Server|Adapter|Node|Host $ts3_VirtualServer;

    public function setUp(): void
    {
        //proof test active
        if (file_exists('./.env.testing')) {
            $env = file('./.env.testing');
            //get live server is available
            $this->active = str_replace('DEV_LIVE_SERVER_AVAILABLE=', '', preg_replace('#\n(?!\n)#', '', $env[2]));
            $this->host = str_replace('DEV_LIVE_SERVER_HOST=', '', preg_replace('#\n(?!\n)#', '', $env[3]));
            $this->queryPort = str_replace('DEV_LIVE_SERVER_QUERY_PORT=', '', preg_replace('#\n(?!\n)#', '', $env[4]));
            $this->user = str_replace('DEV_LIVE_SERVER_QUERY_USER=', '', preg_replace('#\n(?!\n)#', '', $env[5]));
            $this->password = str_replace('DEV_LIVE_SERVER_QUERY_USER_PASSWORD=', '', preg_replace('#\n(?!\n)#', '', $env[6]));
            $this->ts3_unit_test_signals = str_replace('DEV_LIVE_SERVER_UNIT_TEST_SIGNALS=', '', preg_replace('#\n(?!\n)#', '', $env[10]));
        } else {
            $this->active = 'false';
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port=9987'.
            '&ssh=1'.
            '&no_query_clients=0'.
            '&blocking=0'.
            '&timeout=30';

        //set duration time
        $this->duration = strtotime('+1 minutes', time());
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     * @throws \Exception
     */
    public function test_ssh_signal_on_wait_timeout()
    {
        if ($this->active == 'false' || $this->ts3_unit_test_signals == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        try {
            // Connect to the specified server, authenticate and spawn an object for the virtual server
            $this->ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        } catch(TeamSpeak3Exception $e) {
            //catch exception
            echo $e->getMessage();
        }

        // Register a callback for serverqueryWaitTimeout events
        Signal::getInstance()->subscribe('serverqueryWaitTimeout', [$this, 'onWaitTimeout']);

        // Register for server events
        $this->ts3_VirtualServer->serverGetSelected()->notifyRegister('server');

        try {
            while (true) {
                $this->ts3_VirtualServer->getParent()->getAdapter()->wait();
            }
        } catch(TeamSpeak3Exception $e) {
            //catch disconnect exception when getParent()->getTransport()->disconnect() -> Sounds crazy TODO what happen here?
            $this->assertEquals("node method 'getTransport()' does not exist", $e->getMessage());
            $this->assertEquals(0, $e->getCode());
        }

        //the real Query Logout after exit the while() Loop
        $this->ts3_VirtualServer->getParent()->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @param  int  $idle_seconds
     * @param  ServerQuery  $serverquery
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws NodeException
     * @throws TransportException
     */
    public function onWaitTimeout(int $idle_seconds, ServerQuery $serverquery): void
    {
        // If the timestamp on the last query is more than 300 seconds (5 minutes) in the past, send 'keepalive'
        // 'keepalive' command is just server query command 'clientupdate' which does nothing without properties. So nothing changes.
        if ($serverquery->getQueryLastTimestamp() < time() - 260) {
            $serverquery->request('clientupdate');
        }

        // Get data every minute
        if ($idle_seconds % 60 == 0) {
            // Resetting lists
            $this->ts3_VirtualServer->clientListReset();
            $this->ts3_VirtualServer->serverGroupListReset();

            // Get servergroup client info
            $this->ts3_VirtualServer->clientList(['client_type' => 0]);
            $servergrouplist = $this->ts3_VirtualServer->serverGroupList(['type' => 1]);

            $servergroup_clientlist = [];
            foreach ($servergrouplist as $servergroup) {
                $servergroup_clientlist[$servergroup->sgid] = count($this->ts3_VirtualServer->serverGroupClientList($servergroup->sgid));
            }

            // Get virtualserver info
            $this->ts3_VirtualServer->getInfo(true, true);
            $this->ts3_VirtualServer->connectionInfo();
        }

        if (time() >= $this->duration) {
            //set transport to null
            $this->ts3_VirtualServer->getParent()->getTransport()->disconnect();
        }
    }
}
