<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Channel;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

class ChannelFeatureTest extends TestCase
{
    private Server $server;

    protected function setUp(): void
    {
        $this->server = TeamSpeak3::factory('mock://serveradmin:secret@127.0.0.1:10022/?server_port=9987');
    }

    protected function tearDown(): void
    {
        if ($this->server->getAdapter()->getTransport()->isConnected()) {
            $this->server->getAdapter()->getTransport()->disconnect();
        }
    }

    /**
     * Test retrieving channel list.
     */
    public function testChannelList(): void
    {
        $channels = $this->server->channelList();

        $this->assertIsArray($channels);
        $this->assertCount(2, $channels);
        $this->assertArrayHasKey(1, $channels);
        $this->assertArrayHasKey(2, $channels);
        $this->assertEquals('Default Channel', $channels[1]['channel_name']);
        $this->assertEquals('UnitTest', $channels[2]['channel_name']);
    }

    /**
     * Test getting a channel by ID.
     */
    public function testChannelGetById(): void
    {
        $channel = $this->server->channelGetById(1);

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertEquals(1, $channel->getId());
        $this->assertEquals('Default Channel', $channel['channel_name']);
        $this->assertEquals(1, $channel['channel_flag_permanent']);
        $this->assertEquals(1, $channel['channel_flag_default']);
    }

    /**
     * Test getting a channel by Name.
     */
    public function testChannelGetByName(): void
    {
        $channel = $this->server->channelGetByName('UnitTest');

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertEquals(2, $channel->getId());
        $this->assertEquals('UnitTest', $channel['channel_name']);
    }

    /**
     * Test channel properties and info.
     */
    public function testChannelProperties(): void
    {
        $channel = $this->server->channelGetById(1);
        $info = $channel->getInfo();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('channel_name', $info);
        $this->assertEquals('Default Channel', $info['channel_name']);
        $this->assertEquals(4, $channel['channel_codec']);
        $this->assertEquals(6, $channel['channel_codec_quality']);
        $this->assertEquals(-1, $channel['channel_maxclients']);
    }

    /**
     * Test creating a channel.
     */
    public function testChannelCreate(): void
    {
        $cid = $this->server->channelCreate([
            'channel_name' => 'New Channel',
            'channel_topic' => 'Topic',
            'channel_flag_permanent' => 1,
        ]);

        $this->assertEquals(3, $cid);

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('channelcreate channel_name=New\sChannel channel_topic=Topic channel_flag_permanent=1'));
    }

    /**
     * Test deleting a channel.
     */
    public function testChannelDelete(): void
    {
        $this->server->channelDelete(2, true);

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('channeldelete cid=2 force=1'));
    }

    /**
     * Test channel move operation.
     */
    public function testChannelMove(): void
    {
        $channel = $this->server->channelGetById(2);
        $channel->move(1, 0);

        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('channelmove cid=2 cpid=1 order=0'));
    }

    /**
     * Test creating and managing multiple channels.
     */
    public function testChannelCreateAndManage(): void
    {
        $cid = $this->server->channelCreate([
            'channel_name' => 'Support Channel',
            'channel_topic' => 'Helpdesk',
        ]);

        $this->assertEquals(3, $cid);

        $this->server->channelDelete($cid);
        /** @var \PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP $transport */
        $transport = $this->server->getAdapter()->getTransport();
        $this->assertTrue($transport->hasSentCommand('channeldelete cid=3 force=0'));
    }

    /**
     * Test channel not found exception.
     */
    public function testChannelNotFoundException(): void
    {
        $this->expectException(ServerQueryException::class);
        $this->expectExceptionCode(0x300);

        $this->server->channelGetByName('NonExistentChannel');
    }
}
