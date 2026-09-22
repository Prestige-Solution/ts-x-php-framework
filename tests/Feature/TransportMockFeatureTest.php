<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Tests\Feature;

use PHPUnit\Framework\TestCase;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\MockServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\MockTCP;

class TransportMockFeatureTest extends TestCase
{
    private MockServerQuery $mockQuery;

    private MockTCP $transport;

    protected function setUp(): void
    {
        $this->mockQuery = new MockServerQuery(['host' => '127.0.0.1', 'port' => 10022]);
        $this->transport = $this->mockQuery->getMockTransport();
    }

    protected function tearDown(): void
    {
        if ($this->transport->isConnected()) {
            $this->transport->disconnect();
        }
    }

    /**
     * Test registering exact custom response.
     */
    public function testRegisterCustomResponse(): void
    {
        $this->mockQuery->registerResponse('customcommand foo=bar', "custom_data=123 value=test\nerror id=0 msg=ok\n");

        $reply = $this->mockQuery->request('customcommand foo=bar');
        $assoc = $reply->toList();

        $this->assertEquals('123', $assoc['custom_data']);
        $this->assertEquals('test', $assoc['value']);
    }

    /**
     * Test registering regex pattern matching response.
     */
    public function testRegisterPatternResponse(): void
    {
        $this->transport->registerPattern('/^custompattern id=(\d+)/', function ($cmd, $matches) {
            $id = $matches[1];

            return "result_id={$id} status=success\nerror id=0 msg=ok\n";
        });

        $reply1 = $this->mockQuery->request('custompattern id=42');
        $this->assertEquals('42', $reply1->toList()['result_id']);

        $reply2 = $this->mockQuery->request('custompattern id=999');
        $this->assertEquals('999', $reply2->toList()['result_id']);
    }

    /**
     * Test response queue for sequential multi-step responses.
     */
    public function testResponseQueue(): void
    {
        $this->mockQuery->queueResponse("step=first\nerror id=0 msg=ok\n");
        $this->mockQuery->queueResponse("step=second\nerror id=0 msg=ok\n");

        $reply1 = $this->mockQuery->request('anycommand');
        $this->assertEquals('first', $reply1->toList()['step']);

        $reply2 = $this->mockQuery->request('anycommand');
        $this->assertEquals('second', $reply2->toList()['step']);
    }

    /**
     * Test custom command handler callback.
     */
    public function testCommandHandlerCallback(): void
    {
        $this->mockQuery->setCommandHandler(function ($cmd) {
            if (str_starts_with($cmd, 'calc ')) {
                $num = (int) substr($cmd, 5);

                return 'result='.($num * 2);
            }

            return null; // fallback to default
        });

        $reply = $this->mockQuery->request('calc 21');
        $this->assertEquals('42', $reply->toList()['result']);

        // Non-calc command falls back to default
        $versionReply = $this->mockQuery->request('version');
        $this->assertArrayHasKey('version', $versionReply->toList());
    }

    /**
     * Test tracking of sent commands.
     */
    public function testCommandTracking(): void
    {
        $this->mockQuery->request('whoami');
        $this->mockQuery->request('version');
        $this->mockQuery->request('clientlist');

        $commands = $this->mockQuery->getSentCommands();
        $this->assertCount(3, $commands);
        $this->assertEquals('whoami', $commands[0]);
        $this->assertEquals('version', $commands[1]);
        $this->assertEquals('clientlist', $commands[2]);

        $this->assertEquals('clientlist', $this->mockQuery->getLastCommand());
        $this->assertTrue($this->mockQuery->hasSentCommand('whoami'));
        $this->assertFalse($this->mockQuery->hasSentCommand('nonexistent'));
    }

    /**
     * Test resetting mock state.
     */
    public function testMockReset(): void
    {
        $this->mockQuery->registerResponse('test', 'data=1');
        $this->mockQuery->request('whoami');

        $this->transport->reset();

        $this->assertEmpty($this->transport->getSentCommands());
        $this->assertFalse($this->transport->isConnected());
    }

    /**
     * Test simulated ServerQuery error response exception.
     */
    public function testSimulatedServerErrorException(): void
    {
        $this->mockQuery->registerResponse('failcmd', "error id=2568 msg=insufficient\sclient\spermissions\n");

        $this->expectException(ServerQueryException::class);
        $this->expectExceptionCode(0xA08);
        $this->expectExceptionMessage('insufficient client permissions');

        $this->mockQuery->request('failcmd');
    }

    /**
     * Test SSH prompt and ANSI sequence simulation simultaneously.
     */
    public function testPromptAndAnsiSimulationTogether(): void
    {
        $this->transport->setSimulatePrompt(true, 'custom-bot@9987(1):online> ');
        $this->transport->setSimulateAnsi(true);

        $reply = $this->mockQuery->request('whoami');
        $this->assertEquals('serveradmin', $reply->toList()['client_nickname']);
    }
}
