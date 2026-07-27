<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\DevLiveServer;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\FileTransferException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
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

    private string $testPath = DIRECTORY_SEPARATOR . 'tests\testsources';

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
        file_put_contents(getcwd() . $this->testPath .'\test.txt', $content->toString());

        $this->assertFileExists(getcwd() . $this->testPath .'\test.txt');
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

        if(count($cFileList) > 0)
        {
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
        $iconId = $ts3_Host->iconUpload(getcwd() . $this->testPath . '\icons\upload\ChannelAdmin.png');
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
        $ts3_Host->iconUpload(getcwd() . $this->testPath . '\icons\upload\ChannelAdmin.png');
        $iconList = $ts3_Host->iconList();

        if ($iconList === []) {
            $this->markTestIncomplete('No icons available for download');
        }

        $downloadPath = getcwd().$this->testPath.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.'download';

        foreach ($iconList as $icon) {
            $this->assertArrayHasKey('name', $icon);

            $content = $ts3_Host->iconDownload($icon['name']);

            $this->assertNotNull($content);

            $targetFile = $downloadPath.DIRECTORY_SEPARATOR.$icon['name'];
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
        $iconID = $ts3_Host->iconUpload(getcwd() . $this->testPath . '\icons\upload\ChannelAdmin.png');

        $ts3_Host->iconDelete('icon_'.$iconID);

        $iconList = $ts3_Host->iconList();
        $this->assertNotContains('icon_'.$iconID, array_column($iconList, 'name'));

        $ts3_Host->getAdapter()->getTransport()->disconnect();
    }

}
