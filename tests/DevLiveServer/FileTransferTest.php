<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\FileTransferException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class FileTransferTest extends TestCase
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

    private string $serverPort;

    private string $user;

    private string $password;

    private string $ts3_server_uri;

    private string $testPath = DIRECTORY_SEPARATOR.'tests\testsources';

    private int $sgid;

    private string $ts3_unit_test_channel_name;

    private int $test_cid;

    private string $user_test_active;

    private string $ts3_unit_test_userName;

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
            $this->serverPort = str_replace('DEV_LIVE_SERVER_UNIT_TEST_SERVER_PORT=', '', preg_replace('#\n(?!\n)#', '', $env[12]));
            $this->ts3_unit_test_channel_name = str_replace('DEV_LIVE_SERVER_UNIT_TEST_CHANNEL=', '', preg_replace('#\n(?!\n)#', '', $env[7]));
            $this->user_test_active = str_replace('DEV_LIVE_SERVER_UNIT_TEST_USER_ACTIVE=', '', preg_replace('#\n(?!\n)#', '', $env[8]));
            $this->ts3_unit_test_userName = str_replace('DEV_LIVE_SERVER_UNIT_TEST_USER=', '', preg_replace('#\n(?!\n)#', '', $env[9]));
        } else {
            $this->active = 'false';
        }

        $this->ts3_server_uri = 'serverquery://'.$this->user.':'.$this->password.'@'.$this->host.':'.$this->queryPort.
            '/?server_port='.$this->serverPort.
            '&no_query_clients=0'.
            '&blocking=0'.
            '&timeout=30';
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     * @throws \Exception
     */
    public function test_can_file_upload(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);

        $channel = $ts3_Host->channelGetByName('UnitTest');
        $channel->fileUpload('/test.txt', 'Hello World', overwrite: true);
        $cFileList = $channel->fileList();

        $this->assertCount(1, $cFileList);
        $this->assertEquals('test.txt', $cFileList[0]['name']);

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws FileTransferException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_file_download(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);

        $channel = $ts3_Host->channelGetByName('UnitTest');
        $content = $channel->fileDownload('/test.txt');
        file_put_contents(getcwd().$this->testPath.'\test.txt', $content->toString());

        $this->assertFileExists(getcwd().$this->testPath.'\test.txt');
        $this->assertEquals('Hello World', $content->toString());

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function test_can_file_delete(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);
        $channel = $ts3_Host->channelGetByName('UnitTest');
        $cFileList = $channel->fileList();

        if (count($cFileList) > 0) {
            $channel->fileDelete('', '/test.txt');
        }

        $cFileList = $channel->fileList();
        $this->assertCount(0, $cFileList);

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws FileTransferException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_icon_upload(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);
        $iconId = $ts3_Host->iconUpload(getcwd().$this->testPath.'\icons\upload\ChannelAdmin.png');
        $iconList = $ts3_Host->iconList();

        $this->assertNotEmpty($iconList);
        $this->assertContains('icon_'.$iconId, array_column($iconList, 'name'));

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws FileTransferException
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_icon_download(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);
        $ts3_Host->iconUpload(getcwd().$this->testPath.'\icons\upload\ChannelAdmin.png');
        $iconList = $ts3_Host->iconList();

        if ($iconList === []) {
            $this->markTestIncomplete('No icons available for download');
        }

        $downloadPath = getcwd().$this->testPath.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'download';

        foreach ($iconList as $icon) {
            $this->assertArrayHasKey('name', $icon);

            $content = $ts3_Host->iconDownload($icon['name']);

            $this->assertNotNull($content);

            $targetFile = $downloadPath.DIRECTORY_SEPARATOR.$icon['name'].'.png';
            file_put_contents($targetFile, $content->toString());

            $this->assertFileExists($targetFile);
            $this->assertGreaterThan(0, filesize($targetFile));
        }

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws AdapterException
     * @throws FileTransferException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function test_can_icon_delete(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_Host = TeamSpeak3::factory($this->ts3_server_uri);
        $iconID = $ts3_Host->iconUpload(getcwd().$this->testPath.'\icons\upload\ChannelAdmin.png');

        $ts3_Host->iconDelete('icon_'.$iconID);

        $iconList = $ts3_Host->iconList();
        $this->assertNotContains('icon_'.$iconID, array_column($iconList, 'name'));

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws NodeException
     * @throws FileTransferException
     * @throws HelperException
     */
    public function test_can_upload_servergroup_icon(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($ts3_VirtualServer);

        $iconFile = getcwd().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'testsources'.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'Registered.png';
        $iconId = $ts3_VirtualServer->iconUpload($iconFile);
        $serverGroup = $ts3_VirtualServer->serverGroupGetById($this->sgid);
        $serverGroup->permAssign(['i_icon_id'], $iconId);

        $permissions = $serverGroup->permList(true);

        $this->assertArrayHasKey('i_icon_id', $permissions);
        $this->assertSame($iconId, (int) $permissions['i_icon_id']['permvalue']);

        $this->unset_play_test_servergroup($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws HelperException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws NodeException
     * @throws FileTransferException
     */
    public function test_can_download_servergroup_icon(): void
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $this->set_play_test_servergroup($ts3_VirtualServer);

        $iconFile = getcwd().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'testsources'.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'Registered.png';
        $this->assertFileExists($iconFile);
        $this->assertGreaterThan(0, filesize($iconFile));

        $iconId = $ts3_VirtualServer->iconUpload($iconFile);
        $iconName = 'icon_'.$iconId;

        $serverGroup = $ts3_VirtualServer->serverGroupGetById($this->sgid);
        $serverGroup->permAssign(['i_icon_id'], $iconId);

        $ts3_VirtualServer->serverGroupListReset();
        $serverGroup = $ts3_VirtualServer->serverGroupGetById($this->sgid);
        $content = $serverGroup->iconDownload();

        $this->assertNotNull($content);
        $this->assertGreaterThan(0, strlen($content->toString()));

        $downloadPath = getcwd().$this->testPath.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'download';

        $targetFile = $downloadPath.DIRECTORY_SEPARATOR.$iconName.'.png';
        file_put_contents($targetFile, $content->toString());

        $this->assertFileExists($targetFile);
        $this->assertGreaterThan(0, filesize($targetFile));

        $ts3_VirtualServer->iconDelete($iconId);
        $this->unset_play_test_servergroup($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws FileTransferException
     * @throws HelperException
     */
    public function test_can_upload_channel_icon()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $cid = $this->set_play_test_channel($ts3_VirtualServer);

        $iconFile = getcwd().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'testsources'.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'Guest.png';
        $iconId = $ts3_VirtualServer->iconUpload($iconFile);
        $signedIconId = $iconId > 0x7FFFFFFF ? $iconId - 0x100000000 : $iconId;

        $channel = $ts3_VirtualServer->channelGetById($cid);
        $channel->permAssign(['i_icon_id'], $signedIconId);

        $permissions = $channel->permList(true);

        $this->assertArrayHasKey('i_icon_id', $permissions);
        $this->assertSame($signedIconId, (int) $permissions['i_icon_id']['permvalue']);

        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
    }

    /**
     * @throws HelperException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws FileTransferException
     */
    public function test_can_download_channel_icon()
    {
        if ($this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);
        $cid = $this->set_play_test_channel($ts3_VirtualServer);

        $iconFile = getcwd().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'testsources'.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'Guest.png';
        $this->assertFileExists($iconFile);
        $this->assertGreaterThan(0, filesize($iconFile));

        $iconId = $ts3_VirtualServer->iconUpload($iconFile);
        $signedIconId = $iconId > 0x7FFFFFFF ? $iconId - 0x100000000 : $iconId;
        $iconName = 'icon_'.$iconId;

        $channel = $ts3_VirtualServer->channelGetById($cid);
        $channel->permAssign(['i_icon_id'], $signedIconId);

        $permissions = $channel->permList(true);

        $this->assertArrayHasKey('i_icon_id', $permissions);
        $this->assertSame($signedIconId, (int) $permissions['i_icon_id']['permvalue']);

        $ts3_VirtualServer->channelListReset();
        $channel = $ts3_VirtualServer->channelGetById($cid);
        $content = $channel->iconDownload();

        $this->assertNotNull($content);
        $this->assertGreaterThan(0, strlen($content->toString()));

        $downloadPath = getcwd().$this->testPath.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'download';
        $targetFile = $downloadPath.DIRECTORY_SEPARATOR.$iconName.'.png';

        file_put_contents($targetFile, $content->toString());

        $this->assertFileExists($targetFile);
        $this->assertGreaterThan(0, filesize($targetFile));

        $ts3_VirtualServer->iconDelete($iconId);
        $this->unset_play_test_channel($ts3_VirtualServer);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();
        $this->assertFalse($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws FileTransferException
     * @throws HelperException
     */
    public function test_can_download_client_icon()
    {
        if ($this->user_test_active == 'false' || $this->active == 'false') {
            $this->markTestSkipped('DevLiveServer ist not active');
        }

        $ts3_VirtualServer = TeamSpeak3::factory($this->ts3_server_uri);

        $iconFile = getcwd().DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'testsources'.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'upload'.DIRECTORY_SEPARATOR.'Voice.png';
        $this->assertFileExists($iconFile);
        $this->assertGreaterThan(0, filesize($iconFile));

        $iconId = $ts3_VirtualServer->iconUpload($iconFile);
        $signedIconId = $iconId > 0x7FFFFFFF ? $iconId - 0x100000000 : $iconId;
        $iconName = 'icon_'.$iconId;

        $client = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName);
        $client->permAssign(['i_icon_id'], $signedIconId);

        $permissions = $client->permList(true);

        $this->assertArrayHasKey('i_icon_id', $permissions);
        $this->assertSame($signedIconId, (int) $permissions['i_icon_id']['permvalue']);

        $ts3_VirtualServer->clientListReset();
        $client = $ts3_VirtualServer->clientGetByName($this->ts3_unit_test_userName);
        $content = $client->iconDownload();

        $this->assertNotNull($content);
        $this->assertGreaterThan(0, strlen($content->toString()));

        $downloadPath = getcwd().$this->testPath.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'download';
        $targetFile = $downloadPath.DIRECTORY_SEPARATOR.$iconName.'.png';

        file_put_contents($targetFile, $content->toString());

        $this->assertFileExists($targetFile);
        $this->assertGreaterThan(0, filesize($targetFile));

        $client->permRemove(['i_icon_id']);
        $ts3_VirtualServer->iconDelete($iconId);
        $ts3_VirtualServer->getAdapter()->getTransport()->disconnect();

        $this->assertFalse($ts3_VirtualServer->getAdapter()->getTransport()->isConnected());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    private function set_play_test_servergroup(Server $ts3VirtualServer): void
    {
        $this->sgid = $ts3VirtualServer->serverGroupCreate('UnitTest', 1);
    }

    /**
     * @param  Server  $ts3_VirtualServer
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function unset_play_test_servergroup(Server $ts3_VirtualServer): void
    {
        $ts3_VirtualServer->serverGroupDelete($this->sgid);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    private function set_play_test_channel(Server $ts3VirtualServer): int
    {
        $cid = $ts3VirtualServer->channelGetByName($this->ts3_unit_test_channel_name)->getId();

        $createdCID = $ts3VirtualServer->channelCreate(['channel_name' => 'Play-Test', 'channel_flag_permanent' => 1, 'cpid' => $cid]);
        $this->test_cid = $createdCID;

        return $createdCID;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function unset_play_test_channel($ts3_VirtualServer): void
    {
        $ts3_VirtualServer->channelDelete($this->test_cid, true);
    }
}
