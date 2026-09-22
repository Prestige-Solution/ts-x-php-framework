<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Transport;

use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\Adapter;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;

class MockTCP extends TSssh
{
    public const S_WELCOME_L0 = 'TS3';

    public const S_WELCOME_L1 = 'Welcome to the TeamSpeak 3 ServerQuery interface, type "help" for a list of commands and "help <command>" for information on a specific command.';

    public const S_ERROR_OK = 'error id=0 msg=ok';

    public const CMD = [
        'login serveradmin secret' => self::S_ERROR_OK,
        'login client_login_name=serveradmin client_login_password=secret' => self::S_ERROR_OK,
    ];

    /**
     * Default responses for standard TS3 ServerQuery commands.
     */
    protected const DEFAULT_RESPONSES = [
        'logout' => self::S_ERROR_OK,
        'quit' => self::S_ERROR_OK,
        'servernotifyunregister' => self::S_ERROR_OK,
        'whoami' => "virtualserver_status=online virtualserver_id=1 virtualserver_unique_identifier=mock_server_uid virtualserver_port=9987 client_id=1 client_channel_id=1 client_nickname=serveradmin client_database_id=1 client_login_name=serveradmin client_unique_identifier=serveradmin_uid client_origin_server_id=0\nerror id=0 msg=ok",
        'version' => "version=3.13.7\s[Build:\s1600000000] build=1600000000 platform=Linux\nerror id=0 msg=ok",
        'serverinfo' => "virtualserver_id=1 virtualserver_port=9987 virtualserver_name=TeamSpeak\s]Test[\sServer virtualserver_welcomemessage=Welcome\sto\sTeamSpeak virtualserver_platform=Linux virtualserver_version=3.13.7\s[Build:\s1600000000] virtualserver_maxclients=32 virtualserver_clientsonline=2 virtualserver_channelsonline=2 virtualserver_uptime=123456 virtualserver_status=online virtualserver_autostart=1 virtualserver_ask_for_privilegekey=0 virtualserver_unique_identifier=mock_server_uid virtualserver_flag_password=0\nerror id=0 msg=ok",
        'serverlist' => "virtualserver_id=1 virtualserver_port=9987 virtualserver_status=online virtualserver_clientsonline=2 virtualserver_queryclientsonline=1 virtualserver_maxclients=32 virtualserver_uptime=123456 virtualserver_name=TeamSpeak\s]Test[\sServer virtualserver_autostart=1 virtualserver_machine_id virtualserver_unique_identifier=mock_server_uid\nerror id=0 msg=ok",
        'serverlist -all' => "virtualserver_id=1 virtualserver_port=9987 virtualserver_status=online virtualserver_clientsonline=2 virtualserver_queryclientsonline=1 virtualserver_maxclients=32 virtualserver_uptime=123456 virtualserver_name=TeamSpeak\s]Test[\sServer virtualserver_autostart=1 virtualserver_machine_id virtualserver_unique_identifier=mock_server_uid\nerror id=0 msg=ok",
        'servergrouplist' => "sgid=6 name=Server\sAdmin type=1 iconid=300 savedb=1 sortid=0 namemode=0 n_modifyp=75 n_member_addp=75 n_member_removep=75|sgid=7 name=Normal type=1 iconid=0 savedb=1 sortid=0 namemode=0 n_modifyp=75 n_member_addp=75 n_member_removep=75\nerror id=0 msg=ok",
        'channelgrouplist' => "cgid=5 name=Channel\sAdmin type=1 iconid=100 savedb=1 sortid=0 namemode=0 n_modifyp=75 n_member_addp=75 n_member_removep=75|cgid=6 name=Operator type=1 iconid=0 savedb=1 sortid=0 namemode=0 n_modifyp=75 n_member_addp=75 n_member_removep=75\nerror id=0 msg=ok",
        'instanceinfo' => "serverinstance_database_version=23 serverinstance_filetransfer_port=30033 serverinstance_max_download_total_bandwidth=18446744073709551615 serverinstance_max_upload_total_bandwidth=18446744073709551615 serverinstance_guest_serverquery_group=1 serverinstance_template_serveradmin_group=3 serverinstance_template_serverdefault_group=5 serverinstance_template_channeladmin_group=1 serverinstance_template_channeldefault_group=4 serverinstance_permissions_version=15\nerror id=0 msg=ok",
    ];

    protected bool $connected = false;

    protected ?string $reply = null;

    protected array $buffer = [];

    protected array $customResponses = [];

    protected array $patternResponses = [];

    protected array $responseQueue = [];

    /** @var callable|null */
    protected $commandHandler = null;

    protected array $sentCommands = [];

    protected string $transportType = 'ssh';

    protected bool $simulatePrompt = false;

    protected string $promptString = 'ts-bot-dev@9987(1):online> ';

    protected bool $simulateAnsi = false;

    protected bool $failConnect = false;

    protected bool $failLogin = false;

    protected bool $failFingerprint = false;

    protected bool $simulateDropConnection = false;

    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->config = array_merge([
            'host' => 'localhost',
            'port' => 10022,
            'blocking' => 0,
        ], $config);

        if (isset($config['transport_type'])) {
            $this->transportType = (string) $config['transport_type'];
        }
        if (isset($config['simulate_prompt'])) {
            $this->simulatePrompt = (bool) $config['simulate_prompt'];
        }
        if (isset($config['simulate_ansi'])) {
            $this->simulateAnsi = (bool) $config['simulate_ansi'];
        }
        if (isset($config['fail_connect'])) {
            $this->failConnect = (bool) $config['fail_connect'];
        }
        if (isset($config['fail_login'])) {
            $this->failLogin = (bool) $config['fail_login'];
        }
        if (isset($config['fail_fingerprint'])) {
            $this->failFingerprint = (bool) $config['fail_fingerprint'];
        }
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        if ($this->failConnect) {
            throw new TransportException("Connection to {$this->config['host']}:{$this->config['port']} failed");
        }

        if (! empty($this->config['fingerprint'])) {
            $hostKey = $this->config['simulate_host_key'] ?? 'rsa-sha2-512 AAAAB3NzaC1yc2EAAAADAQABAAABAQC3r7Yh5N1xXj1234567890abcdefghijklmnopqrstuvwxyz';
            if ($this->failFingerprint || ! empty($this->config['fail_fingerprint'])) {
                $hostKey = 'rsa-sha2-512 AAAAB3NzaC1yc2EAAAADAQABAAABAAAAA_different_key';
            }
            $this->verifyFingerprint($hostKey, (string) $this->config['fingerprint']);
        }

        if ($this->failLogin) {
            throw new TransportException('Login failed: incorrect username or password');
        }

        $this->connected = true;
        $this->reply = sprintf("%s\n%s\n", self::S_WELCOME_L0, self::S_WELCOME_L1);
        $this->buffer = explode("\n", trim($this->reply));
    }

    public function isConnected(): bool
    {
        return $this->connected && ! $this->simulateDropConnection;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->reply = null;
        $this->buffer = [];

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'Disconnected', $this);
    }

    /**
     * Simulated reading of a line (SSH / TCP read)
     * @throws TransportException
     */
    public function readLine(int $timeout = 1, string $token = "\n"): ?StringHelper
    {
        if (! $this->connected) {
            $this->connect();
        }

        if ($this->simulateDropConnection) {
            $this->connected = false;
            throw new TransportException(
                "Connection to server '{$this->config['host']}:{$this->config['port']}' lost"
            );
        }

        $line = '';

        while (! str_ends_with($line, $token)) {
            $data = $this->read();

            $str = $data->toString();
            if ($str === '' || $str === "\n") {
                if ($line !== '') {
                    $line .= $token;
                    break;
                }

                if (empty($this->reply) && empty($this->buffer)) {
                    return null;
                }
            }

            $line .= $str;
        }

        if ($this->transportType === 'ssh' || $this->simulatePrompt) {
            $line = trim($line, "\0\t\n\r\x0B");
            $line = preg_replace('/^[A-Za-z0-9\-_@()]+:[^\s>]*>\s*/', '', $line);
        }

        return StringHelper::factory($line)->trim();
    }

    /**
     * Read mock data with ANSI filter simulation
     * @throws TransportException
     */
    public function read(int $length = 4096): StringHelper
    {
        if (! $this->connected) {
            $this->connect();
        }

        if ($this->simulateDropConnection) {
            $this->connected = false;
            throw new TransportException(
                "Connection to server '{$this->config['host']}:{$this->config['port']}' lost"
            );
        }

        if (empty($this->reply)) {
            return StringHelper::factory('');
        }

        $lines = explode("\n", $this->reply);
        $data = array_shift($lines);
        $this->reply = implode("\n", $lines);

        $data = substr($data, 0, $length);

        if ($this->transportType === 'ssh' || $this->simulateAnsi) {
            $data = preg_replace([
                '/\x1B\[[0-?]*[ -\/]*[@-~]/',
                '/\x1B][^\x07]*\x07/',
                '/\x1B\[?=\d*[A-Za-z]/',
            ], '', $data);
        }

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataRead', $data);

        return StringHelper::factory($data."\n");
    }

    /**
     * Send command and generate mock reply
     * @throws TransportException
     */
    public function write(string $data): void
    {
        if ($this->simulateDropConnection) {
            $this->connected = false;
            throw new TransportException(
                "Connection to server '{$this->config['host']}:{$this->config['port']}' lost"
            );
        }

        if (! $this->isConnected()) {
            throw new TransportException('Not connected to mock server');
        }

        $this->sentCommands[] = $data;

        Signal::getInstance()->emit(strtolower($this->getAdapterType()).'DataSend', $data);

        $response = $this->resolveResponse($data);

        if ($this->simulateAnsi) {
            $response = "\x1B[32m".$response."\x1B[0m";
        }

        if ($this->simulatePrompt) {
            $response = $this->promptString.$response;
        }

        $this->reply = $response;
        $this->buffer = explode("\n", trim($this->reply));
    }

    public function sendLine(string $data, string $separator = "\n"): void
    {
        $this->write($data);
    }

    public function send(string $data): void
    {
        $this->write(rtrim($data, "\r\n"));
    }

    /**
     * Resolve the response for a given ServerQuery command.
     */
    protected function resolveResponse(string $cmd): string
    {
        // 1. Response queue
        if (! empty($this->responseQueue)) {
            $queued = array_shift($this->responseQueue);

            return $this->formatReply($queued);
        }

        // 2. Custom callable handler
        if ($this->commandHandler !== null) {
            $handled = call_user_func($this->commandHandler, $cmd);
            if ($handled !== null) {
                return $this->formatReply($handled);
            }
        }

        // 3. Exact custom response
        if (isset($this->customResponses[$cmd])) {
            return $this->formatReply($this->customResponses[$cmd]);
        }

        // 4. Exact CMD constant
        if (isset(self::CMD[$cmd])) {
            return $this->formatReply(self::CMD[$cmd]);
        }

        // 5. Pattern-based custom responses
        foreach ($this->patternResponses as $pattern => $handler) {
            if (preg_match($pattern, $cmd, $matches)) {
                $resp = is_callable($handler) ? $handler($cmd, $matches) : $handler;

                return $this->formatReply($resp);
            }
        }

        // 6. Default command dictionary
        if (isset(self::DEFAULT_RESPONSES[$cmd])) {
            return $this->formatReply(self::DEFAULT_RESPONSES[$cmd]);
        }

        // 7. Dynamic built-in handlers based on command prefix/type
        return $this->formatReply($this->resolveDynamicDefaultResponse($cmd));
    }

    /**
     * Formats reply ensuring proper newline and error line.
     */
    protected function formatReply(string $reply): string
    {
        $reply = rtrim($reply, "\r\n");

        if (! str_contains($reply, 'error id=')) {
            $reply .= "\n".self::S_ERROR_OK;
        }

        return $reply."\n";
    }

    /**
     * Resolves dynamic TS3 ServerQuery commands.
     */
    protected function resolveDynamicDefaultResponse(string $cmd): string
    {
        $parts = explode(' ', $cmd);
        $action = $parts[0] ?? '';

        switch ($action) {
            case 'login':
            case 'logout':
            case 'use':
            case 'clientupdate':
            case 'channeledit':
            case 'channeldelete':
            case 'channelmove':
            case 'clientpoke':
            case 'clientkick':
            case 'clientmove':
            case 'sendtextmessage':
            case 'gm':
            case 'serverstart':
            case 'serverstop':
            case 'serverdelete':
            case 'servernotifyregister':
            case 'servernotifyunregister':
            case 'servergroupaddclient':
            case 'servergroupdelclient':
            case 'servergroupdel':
            case 'channelgroupdel':
            case 'privilegekeydelete':
            case 'privilegekeyuse':
                return self::S_ERROR_OK;

            case 'servergroupadd':
                return "sgid=10\n".self::S_ERROR_OK;

            case 'servergroupcopy':
                return "sgid=11\n".self::S_ERROR_OK;

            case 'channelgroupadd':
                return "cgid=10\n".self::S_ERROR_OK;

            case 'channelgroupcopy':
                return "cgid=11\n".self::S_ERROR_OK;

            case 'clientdbinfo':
                return "cldbid=1 client_database_id=1 client_unique_identifier=mock_admin_uid client_nickname=serveradmin\n".self::S_ERROR_OK;

            case 'permoverview':
                return "permid=1 permvalue=100 permskip=0 permnegated=0\n".self::S_ERROR_OK;

            case 'serveridgetbyport':
            case 'serveridgetbyname':
                return "server_id=1\n".self::S_ERROR_OK;

            case 'serverlist':
                return "virtualserver_id=1 virtualserver_port=9987 virtualserver_status=online virtualserver_clientsonline=2 virtualserver_queryclientsonline=1 virtualserver_maxclients=32 virtualserver_uptime=123456 virtualserver_name=TeamSpeak\s]Test[\sServer virtualserver_autostart=1 virtualserver_machine_id virtualserver_unique_identifier=mock_server_uid\n".self::S_ERROR_OK;

            case 'servercreate':
                return "sid=2 token=mock_token_abcdef123456\n".self::S_ERROR_OK;

            case 'channellist':
                return "cid=1 pid=0 channel_order=0 channel_name=Default\sChannel channel_topic channel_flag_default=1 channel_flag_password=0 channel_flag_permanent=1 channel_flag_semi_permanent=0 channel_codec=4 channel_codec_quality=6 channel_needed_talk_power=0 total_clients=1|cid=2 pid=0 channel_order=1 channel_name=UnitTest channel_topic=UnitTest\sTopic channel_flag_default=0 channel_flag_password=0 channel_flag_permanent=1 channel_flag_semi_permanent=0 channel_codec=4 channel_codec_quality=6 channel_needed_talk_power=0 total_clients=1\n".self::S_ERROR_OK;

            case 'channelinfo':
                return "channel_name=Default\sChannel channel_topic channel_description channel_password channel_codec=4 channel_codec_quality=6 channel_maxclients=-1 channel_maxfamilyclients=-1 channel_order=0 channel_flag_permanent=1 channel_flag_semi_permanent=0 channel_flag_default=1 channel_flag_password=0 channel_codec_latency_factor=1 channel_codec_is_unencrypted=1 channel_security_salt channel_delete_delay=0 channel_needed_talk_power=0 channel_forced_silence=0 channel_name_phonetic channel_icon_id=0\n".self::S_ERROR_OK;

            case 'channelcreate':
                return "cid=3\n".self::S_ERROR_OK;

            case 'clientlist':
                return "clid=1 cid=1 client_database_id=1 client_nickname=serveradmin client_type=1|clid=2 cid=1 client_database_id=2 client_nickname=UnitTestUser client_type=0 client_unique_identifier=mock_user_uid_123 client_away=0 client_input_muted=0 client_output_muted=0 client_is_talker=0 client_channel_group_id=5 client_servergroups=6 client_created=1600000000 client_lastconnected=1600000100 client_totalconnections=10\n".self::S_ERROR_OK;

            case 'clientinfo':
                return "cid=1 client_database_id=2 client_nickname=UnitTestUser client_type=0 client_unique_identifier=mock_user_uid_123 client_flag_avatar client_description client_month_bytes_uploaded=0 client_month_bytes_downloaded=0 client_total_bytes_uploaded=0 client_total_bytes_downloaded=0 client_icon_id=0 client_channel_group_id=5 client_servergroups=6 client_away=0 client_away_message client_talk_power=75 client_is_talker=0 client_is_priority_speaker=0 client_is_recording=0 client_is_channel_commander=0 connection_client_ip=127.0.0.1\n".self::S_ERROR_OK;

            case 'clientfind':
                return "clid=2 client_nickname=UnitTestUser\n".self::S_ERROR_OK;

            case 'clientfinddb':
                return "cldbid=2\n".self::S_ERROR_OK;

            case 'clientgetids':
                return "cluid=mock_user_uid_123 clid=2 name=UnitTestUser\n".self::S_ERROR_OK;

            case 'clientgetdbidfromuid':
                return "cluid=mock_user_uid_123 cldbid=2\n".self::S_ERROR_OK;

            case 'clientgetnamefromuid':
            case 'clientgetnamefromdbid':
                return "cluid=mock_user_uid_123 cldbid=2 name=UnitTestUser\n".self::S_ERROR_OK;

            case 'servergroupclientlist':
                return "cldbid=2 client_nickname=UnitTestUser client_unique_identifier=mock_user_uid_123\n".self::S_ERROR_OK;

            case 'channelgroupclientlist':
                return "cid=1 cldbid=2 cgid=5\n".self::S_ERROR_OK;

            case 'permissionlist':
                return "permid=1 permname=b_serverinstance_help_view permdesc=view\shelp permsid=b_serverinstance_help_view|permid=2 permname=b_serverinstance_version_view permdesc=view\sversion permsid=b_serverinstance_version_view\n".self::S_ERROR_OK;

            case 'permfind':
                return "id1=6 id2=0 p=1 v=1 n=0 a=0\n".self::S_ERROR_OK;

            case 'permget':
                return "permid=1 permvalue=1 permnegated=0 permskip=0\n".self::S_ERROR_OK;

            case 'privilegekeylist':
                return "token=mock_token_123456 token_type=0 token_id1=6 token_id2=0 token_description token_customset\n".self::S_ERROR_OK;

            case 'privilegekeyadd':
                return "token=mock_token_created_123\n".self::S_ERROR_OK;

            case 'help':
                return "help command list\n".self::S_ERROR_OK;

            default:
                return 'error id=1 msg=unknown_command';
        }
    }

    public function registerResponse(string $cmd, string $reply): self
    {
        $this->customResponses[$cmd] = $reply;

        return $this;
    }

    public function registerPattern(string $pattern, string|callable $reply): self
    {
        $this->patternResponses[$pattern] = $reply;

        return $this;
    }

    public function queueResponse(string $reply): self
    {
        $this->responseQueue[] = $reply;

        return $this;
    }

    public function setCommandHandler(?callable $handler): self
    {
        $this->commandHandler = $handler;

        return $this;
    }

    public function setTransportType(string $type): self
    {
        $this->transportType = $type;

        return $this;
    }

    public function setSimulatePrompt(bool $enable, string $prompt = 'ts-bot-dev@9987(1):online> '): self
    {
        $this->simulatePrompt = $enable;
        $this->promptString = $prompt;

        return $this;
    }

    public function setSimulateAnsi(bool $enable): self
    {
        $this->simulateAnsi = $enable;

        return $this;
    }

    public function setFailConnect(bool $fail): self
    {
        $this->failConnect = $fail;

        return $this;
    }

    public function setFailLogin(bool $fail): self
    {
        $this->failLogin = $fail;

        return $this;
    }

    public function setSimulateDropConnection(bool $drop): self
    {
        $this->simulateDropConnection = $drop;

        return $this;
    }

    public function getSentCommands(): array
    {
        return $this->sentCommands;
    }

    public function getLastCommand(): ?string
    {
        return ! empty($this->sentCommands) ? end($this->sentCommands) : null;
    }

    public function hasSentCommand(string $command): bool
    {
        return in_array($command, $this->sentCommands, true);
    }

    public function clearSentCommands(): self
    {
        $this->sentCommands = [];

        return $this;
    }

    public function reset(): self
    {
        $this->customResponses = [];
        $this->patternResponses = [];
        $this->responseQueue = [];
        $this->commandHandler = null;
        $this->sentCommands = [];
        $this->simulatePrompt = false;
        $this->simulateAnsi = false;
        $this->failConnect = false;
        $this->failLogin = false;
        $this->simulateDropConnection = false;
        $this->connected = false;
        $this->reply = null;
        $this->buffer = [];

        return $this;
    }

    public function getAdapterType(): string
    {
        if ($this->adapter instanceof ServerQuery) {
            return 'ServerQuery';
        }

        if ($this->adapter instanceof Adapter) {
            return parent::getAdapterType();
        }

        return $this->transportType;
    }
}
