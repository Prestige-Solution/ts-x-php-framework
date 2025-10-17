<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework;

use Exception;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\FileTransfer;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\MockServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Profiler;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Uri;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Server;

/**
 * @class TeamSpeak3
 * @brief Factory class all for TeamSpeak 3 PHP Framework objects.
 */
class TeamSpeak3
{
    /**
     * TeamSpeak 3 protocol welcome message.
     */
    public const TS3_PROTO_IDENT = 'TS3';

    /**
     * TeamSpeak 3 protocol greeting message prefix.
     */
    public const TS3_MOTD_PREFIX = 'Welcome';

    /**
     * TeaSpeak protocol welcome message.
     */
    public const TEA_PROTO_IDENT = 'TeaSpeak';

    /**
     * TeaSpeak protocol greeting message prefix.
     */
    public const TEA_MOTD_PREFIX = 'Welcome';

    /**
     * TeamSpeak 3 protocol error message prefix.
     */
    public const ERROR = 'error';

    /**
     * TeamSpeak 3 protocol event message prefix.
     */
    public const EVENT = 'notify';

    /**
     * TeamSpeak 3 protocol server connection handler ID prefix.
     */
    public const SCHID = 'selected';

    /**
     * TeamSpeak 3 PHP Framework version.
     */
    public const LIB_VERSION = '2.0.0';

    /*@
     * TeamSpeak 3 protocol separators.
     */
    public const SEPARATOR_LINE = "\n"; //!< protocol line separator

    public const SEPARATOR_LIST = '|';  //!< protocol list separator

    public const SEPARATOR_CELL = ' ';  //!< protocol cell separator

    public const SEPARATOR_PAIR = '=';  //!< protocol pair separator

    /*@
     * TeamSpeak 3 API key scopes.
     */
    public const APIKEY_MANAGE = 'manage';  //!< allow access to administrative calls

    public const APIKEY_WRITE = 'write';   //!< allow access to read and write calls

    public const APIKEY_READ = 'read';    //!< allow access to read-only calls

    /*@
     * TeamSpeak 3 log levels.
     */
    public const LOGLEVEL_CRITICAL = 0x00; //!< 0: these messages stop the program

    public const LOGLEVEL_ERROR = 0x01; //!< 1: everything that is awful

    public const LOGLEVEL_WARNING = 0x02; //!< 2: everything that might be bad

    public const LOGLEVEL_DEBUG = 0x03; //!< 3: output that might help find a problem

    public const LOGLEVEL_INFO = 0x04; //!< 4: informational output

    public const LOGLEVEL_DEVEL = 0x05; //!< 5: development output

    /*@
     * TeamSpeak 3 token types.
     */
    public const TOKEN_SERVERGROUP = 0x00; //!< 0: server group token  (id1={groupID} id2=0)

    public const TOKEN_CHANNELGROUP = 0x01; //!< 1: channel group token (id1={groupID} id2={channelID})

    /*@
     * TeamSpeak 3 codec identifiers.
     */
    public const CODEC_SPEEX_NARROWBAND = 0x00; //!< 0: speex narrowband     (mono, 16bit, 8kHz)

    public const CODEC_SPEEX_WIDEBAND = 0x01; //!< 1: speex wideband       (mono, 16bit, 16kHz)

    public const CODEC_SPEEX_ULTRAWIDEBAND = 0x02; //!< 2: speex ultra-wideband (mono, 16bit, 32kHz)

    public const CODEC_CELT_MONO = 0x03; //!< 3: celt mono            (mono, 16bit, 48kHz)

    public const CODEC_OPUS_VOICE = 0x04; //!< 3: opus voice           (interactive)

    public const CODEC_OPUS_MUSIC = 0x05; //!< 3: opus music           (interactive)

    /*@
     * TeamSpeak 3 codec encryption modes.
     */
    public const CODEC_CRYPT_INDIVIDUAL = 0x00; //!< 0: configure per channel

    public const CODEC_CRYPT_DISABLED = 0x01; //!< 1: globally disabled

    public const CODEC_CRYPT_ENABLED = 0x02; //!< 2: globally enabled

    /*@
     * TeamSpeak 3 kick reason types.
     */
    public const KICK_CHANNEL = 0x04; //!< 4: kick client from channel

    public const KICK_SERVER = 0x05; //!< 5: kick client from server

    /*@
     * TeamSpeak 3 text message target modes.
     */
    public const TEXTMSG_CLIENT = 0x01; //!< 1: target is a client

    public const TEXTMSG_CHANNEL = 0x02; //!< 2: target is a channel

    public const TEXTMSG_SERVER = 0x03; //!< 3: target is a virtual server

    /*@
     * TeamSpeak 3 plugin command target modes.
     */
    public const PLUGINCMD_CHANNEL = 0x01; //!< 1: send plugincmd to all clients in current channel

    public const PLUGINCMD_SERVER = 0x02; //!< 2: send plugincmd to all clients on server

    public const PLUGINCMD_CLIENT = 0x03; //!< 3: send plugincmd to all given client ids

    public const PLUGINCMD_CHANNEL_SUBSCRIBED = 0x04; //!< 4: send plugincmd to all subscribed clients in current channel

    /*@
     * TeamSpeak 3 host message modes.
     */
    public const HOSTMSG_NONE = 0x00; //!< 0: display no message

    public const HOSTMSG_LOG = 0x01; //!< 1: display message in chatlog

    public const HOSTMSG_MODAL = 0x02; //!< 2: display message in modal dialog

    public const HOSTMSG_MODALQUIT = 0x03; //!< 3: display message in modal dialog and close connection

    /*@
     * TeamSpeak 3 host banner modes.
     */
    public const HOSTBANNER_NO_ADJUST = 0x00; //!< 0: do not adjust

    public const HOSTBANNER_IGNORE_ASPECT = 0x01; //!< 1: adjust but ignore aspect ratio

    public const HOSTBANNER_KEEP_ASPECT = 0x02; //!< 2: adjust and keep aspect ratio

    /*@
     * TeamSpeak 3 client identification types.
     */
    public const CLIENT_TYPE_REGULAR = 0x00; //!< 0: regular client

    public const CLIENT_TYPE_SERVERQUERY = 0x01; //!< 1: query client

    /*@
     * TeamSpeak 3 permission group database types.
     */
    public const GROUP_DBTYPE_TEMPLATE = 0x00; //!< 0: template group     (used for new virtual servers)

    public const GROUP_DBTYPE_REGULAR = 0x01; //!< 1: regular group      (used for regular clients)

    public const GROUP_DBTYPE_SERVERQUERY = 0x02; //!< 2: global query group (used for ServerQuery clients)

    /*@
     * TeamSpeak 3 permission group name modes.
     */
    public const GROUP_NAMEMODE_HIDDEN = 0x00; //!< 0: display no name

    public const GROUP_NAMEMODE_BEFORE = 0x01; //!< 1: display name before client nickname

    public const GROUP_NAMEMODE_BEHIND = 0x02; //!< 2: display name after client nickname

    /*@
     * TeamSpeak 3 permission group identification types.
     */
    public const GROUP_IDENTIFIY_STRONGEST = 0x01; //!< 1: identify most powerful group

    public const GROUP_IDENTIFIY_WEAKEST = 0x02; //!< 2: identify weakest group

    /*@
     * TeamSpeak 3 permission types.
     */
    public const PERM_TYPE_SERVERGROUP = 0x00; //!< 0: server group permission

    public const PERM_TYPE_CLIENT = 0x01; //!< 1: client specific permission

    public const PERM_TYPE_CHANNEL = 0x02; //!< 2: channel specific permission

    public const PERM_TYPE_CHANNELGROUP = 0x03; //!< 3: channel group permission

    public const PERM_TYPE_CHANNELCLIENT = 0x04; //!< 4: channel-client specific permission

    /*@
     * TeamSpeak 3 permission categories.
     */
    public const PERM_CAT_GLOBAL = 0x10; //!< 00010000: global permissions

    public const PERM_CAT_GLOBAL_INFORMATION = 0x11; //!< 00010001: global permissions -> global information

    public const PERM_CAT_GLOBAL_SERVER_MGMT = 0x12; //!< 00010010: global permissions -> virtual server management

    public const PERM_CAT_GLOBAL_ADM_ACTIONS = 0x13; //!< 00010011: global permissions -> global administrative actions

    public const PERM_CAT_GLOBAL_SETTINGS = 0x14; //!< 00010100: global permissions -> global settings

    public const PERM_CAT_SERVER = 0x20; //!< 00100000: virtual server permissions

    public const PERM_CAT_SERVER_INFORMATION = 0x21; //!< 00100001: virtual server permissions -> virtual server information

    public const PERM_CAT_SERVER_ADM_ACTIONS = 0x22; //!< 00100010: virtual server permissions -> virtual server administrative actions

    public const PERM_CAT_SERVER_SETTINGS = 0x23; //!< 00100011: virtual server permissions -> virtual server settings

    public const PERM_CAT_CHANNEL = 0x30; //!< 00110000: channel permissions

    public const PERM_CAT_CHANNEL_INFORMATION = 0x31; //!< 00110001: channel permissions -> channel information

    public const PERM_CAT_CHANNEL_CREATE = 0x32; //!< 00110010: channel permissions -> create channels

    public const PERM_CAT_CHANNEL_MODIFY = 0x33; //!< 00110011: channel permissions -> edit channels

    public const PERM_CAT_CHANNEL_DELETE = 0x34; //!< 00110100: channel permissions -> delete channels

    public const PERM_CAT_CHANNEL_ACCESS = 0x35; //!< 00110101: channel permissions -> access channels

    public const PERM_CAT_GROUP = 0x40; //!< 01000000: group permissions

    public const PERM_CAT_GROUP_INFORMATION = 0x41; //!< 01000001: group permissions -> group information

    public const PERM_CAT_GROUP_CREATE = 0x42; //!< 01000010: group permissions -> create groups

    public const PERM_CAT_GROUP_MODIFY = 0x43; //!< 01000011: group permissions -> edit groups

    public const PERM_CAT_GROUP_DELETE = 0x44; //!< 01000100: group permissions -> delete groups

    public const PERM_CAT_CLIENT = 0x50; //!< 01010000: client permissions

    public const PERM_CAT_CLIENT_INFORMATION = 0x51; //!< 01010001: client permissions -> client information

    public const PERM_CAT_CLIENT_ADM_ACTIONS = 0x52; //!< 01010010: client permissions -> client administrative actions

    public const PERM_CAT_CLIENT_BASICS = 0x53; //!< 01010011: client permissions -> client basic communication

    public const PERM_CAT_CLIENT_MODIFY = 0x54; //!< 01010100: client permissions -> edit clients

    public const PERM_CAT_FILETRANSFER = 0x60; //!< 01100000: file transfer permissions

    public const PERM_CAT_NEEDED_MODIFY_POWER = 0xFF; //!< 11111111: needed permission modify power (grant) permissions

    /*@
     * TeamSpeak 3 file types.
     */
    public const FILE_TYPE_DIRECTORY = 0x00; //!< 0: file is directory

    public const FILE_TYPE_REGULAR = 0x01; //!< 1: file is regular

    /*@
     * TeamSpeak 3 server snapshot types.
     */
    public const SNAPSHOT_STRING = 0x00; //!< 0: default string

    public const SNAPSHOT_BASE64 = 0x01; //!< 1: base64 string

    public const SNAPSHOT_HEXDEC = 0x02; //!< 2: hexadecimal string

    /*@
     * TeamSpeak 3 channel spacer types.
     */
    public const SPACER_SOLIDLINE = 0x00; //!< 0: solid line

    public const SPACER_DASHLINE = 0x01; //!< 1: dash line

    public const SPACER_DOTLINE = 0x02; //!< 2: dot line

    public const SPACER_DASHDOTLINE = 0x03; //!< 3: dash dot line

    public const SPACER_DASHDOTDOTLINE = 0x04; //!< 4: dash dot dot line

    public const SPACER_CUSTOM = 0x05; //!< 5: custom format

    /*@
     * TeamSpeak 3 channel spacer alignments.
     */
    public const SPACER_ALIGN_LEFT = 0x00; //!< 0: alignment left

    public const SPACER_ALIGN_RIGHT = 0x01; //!< 1: alignment right

    public const SPACER_ALIGN_CENTER = 0x02; //!< 2: alignment center

    public const SPACER_ALIGN_REPEAT = 0x03; //!< 3: repeat until the whole line is filled

    /*@
     * TeamSpeak 3 reason identifiers.
     */
    public const REASON_NONE = 0x00; //!<  0: no reason

    public const REASON_MOVE = 0x01; //!<  1: channel switched or moved

    public const REASON_SUBSCRIPTION = 0x02; //!<  2: subscription added or removed

    public const REASON_TIMEOUT = 0x03; //!<  3: client connection timed out

    public const REASON_CHANNEL_KICK = 0x04; //!<  4: client kicked from channel

    public const REASON_SERVER_KICK = 0x05; //!<  5: client kicked from server

    public const REASON_SERVER_BAN = 0x06; //!<  6: client banned from server

    public const REASON_SERVER_STOP = 0x07; //!<  7: server stopped

    public const REASON_DISCONNECT = 0x08; //!<  8: client disconnected

    public const REASON_CHANNEL_UPDATE = 0x09; //!<  9: channel information updated

    public const REASON_CHANNEL_EDIT = 0x0A; //!< 10: channel information edited

    public const REASON_DISCONNECT_SHUTDOWN = 0x0B; //!< 11: client disconnected on server shutdown

    /**
     * Stores an array containing various chars which need to be escaped while communicating
     * with a TeamSpeak 3 Server.
     *
     * @var array
     */
    protected static array $escape_patterns = [
        '\\' => '\\\\', // backslash
        ' ' => '\\s',  // whitespace
        '|' => '\\p',  // pipe
        ';' => '\\;',  // semicolon
        "\a" => '\\a',  // bell
        "\b" => '\\b',  // backspace
        "\f" => '\\f',  // formfeed
        "\n" => '\\n',  // newline
        "\r" => '\\r',  // carriage return
        "\t" => '\\t',  // horizontal tab
        "\v" => '\\v',   // vertical tab
    ];

    /**
     * Factory for PlanetTeamSpeak\TeamSpeak3Framework\Node\Server classes. $uri must be formatted as
     * "<adapter>://<user>:<pass>@<host>:<port>/<options>#<flags>". All parameters
     *
     * === Supported Options ===
     *   - timeout
     *   - ssh (TeamSpeak only)
     *   - nickname
     *   - no_query_clients
     *   - use_offline_as_virtual
     *   - clients_before_channels
     *   - server_id|server_uid|server_port|server_name
     *   - channel_id|channel_name
     *   - client_id|client_uid|client_name
     *
     * === Supported Flags (only one per $uri) ===
     *   - no_query_clients
     *   - use_offline_as_virtual
     *   - clients_before_channels
     *
     * === URI Examples ===
     * serverquery://<user>:<pass>@<HOST>:<PORT>/?server_port=9987&no_query_clients=0&timeout=30
     * serverquery://<user>:<pass>@<HOST>:<PORT>/?server_port=9987&no_query_clients=0&timeout=30&nickname=UnitTestBot
     *
     * If you have special characters in your password or username, you need to encode them.
     * serverquery://'.rawurlencode(<user>).':'.rawurlencode(<password>) .'@<host>:<query_port>/?server_port=9987&no_query_clients=0&blocking=0&timeout=30&nickname=UnitTestBot
     *
     * @param string $uri
     * @return Host|Server|ServerQuery|MockServerQuery|FileTransfer
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws Exception
     */
    /**
     * Factory for PlanetTeamSpeak\TeamSpeak3Framework\Node\Server classes.
     * @throws Exception
     */
    public static function factory(string $uri): Host|Server|ServerQuery|MockServerQuery|FileTransfer
    {
        self::init();

        $uri = new Uri($uri);

        $adapter = self::getAdapterName($uri->getScheme());
        $options = [
            'host' => $uri->getHost(),
            'port' => $uri->getPort(),
            'timeout' => (int) $uri->getQueryVar('timeout', 10),
            'blocking' => 0,
            'tls' => 0, // TODO maybe unnecessary?
            'ssh' => 1,
        ];

        self::loadClass($adapter);

        $options['username'] = $uri->getUser();
        $options['password'] = $uri->getPass();

        $adapterClass = 'PlanetTeamSpeak\\TeamSpeak3Framework\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $adapter);
        $object = new $adapterClass($options);

        try {
            if ($object instanceof ServerQuery) {
                // Create a host object
                $node = new Host($object);

                if ($uri->hasUser() && $uri->hasPass()) {
                    $node->login($uri->getUser(), $uri->getPass());
                }

                if ($uri->hasQueryVar('nickname')) {
                    $node->setPredefinedQueryName($uri->getQueryVar('nickname'));
                }

                if ($uri->getFragment() == 'use_offline_as_virtual') {
                    $node->setUseOfflineAsVirtual(true);
                } elseif ($uri->hasQueryVar('use_offline_as_virtual')) {
                    $node->setUseOfflineAsVirtual((bool) $uri->getQueryVar('use_offline_as_virtual'));
                }

                if ($uri->getFragment() == 'clients_before_channels') {
                    $node->setLoadClientlistFirst(true);
                } elseif ($uri->hasQueryVar('clients_before_channels')) {
                    $node->setLoadClientlistFirst((bool) $uri->getQueryVar('clients_before_channels'));
                }

                if ($uri->getFragment() == 'no_query_clients') {
                    $node->setExcludeQueryClients(true);
                } elseif ($uri->hasQueryVar('no_query_clients')) {
                    $node->setExcludeQueryClients((bool) $uri->getQueryVar('no_query_clients'));
                }

                // Select server
                if ($uri->hasQueryVar('server_id')) {
                    $node = $node->serverGetById($uri->getQueryVar('server_id'));
                } elseif ($uri->hasQueryVar('server_uid')) {
                    $node = $node->serverGetByUid($uri->getQueryVar('server_uid'));
                } elseif ($uri->hasQueryVar('server_port')) {
                    $node = $node->serverGetByPort($uri->getQueryVar('server_port'));
                } elseif ($uri->hasQueryVar('server_name')) {
                    $node = $node->serverGetByName($uri->getQueryVar('server_name'));
                }

                // Select a channel or client
                if ($node instanceof Server) {
                    if ($uri->hasQueryVar('channel_id')) {
                        $node = $node->channelGetById($uri->getQueryVar('channel_id'));
                    } elseif ($uri->hasQueryVar('channel_name')) {
                        $node = $node->channelGetByName($uri->getQueryVar('channel_name'));
                    }

                    if ($uri->hasQueryVar('client_id')) {
                        $node = $node->clientGetById($uri->getQueryVar('client_id'));
                    }
                    if ($uri->hasQueryVar('client_uid')) {
                        $node = $node->clientGetByUid($uri->getQueryVar('client_uid'));
                    } elseif ($uri->hasQueryVar('client_name')) {
                        $node = $node->clientGetByName($uri->getQueryVar('client_name'));
                    }
                }

                return $node;
            }
        } catch (Exception $e) {
            $object->__destruct();
            throw $e;
        }

        return $object;
    }

    /**
     * Returns the name of an adapter class by $name.
     *
     * @param string $name
     * @param string $namespace
     * @return string
     * @throws AdapterException
     */
    protected static function getAdapterName(string $name, string $namespace = 'Adapter_'): string
    {
        $path = self::getFilePath($namespace);
        $scan = scandir(__DIR__.DIRECTORY_SEPARATOR.$path);

        foreach ($scan as $node) {
            $file = StringHelper::factory($node)->toLower();

            if ($file->startsWith($name) && $file->endsWith('.php')) {
                return $path.str_replace('.php', '', $node);
            }
        }

        throw new AdapterException("adapter '".$name."' does not exist");
    }

    /**
     * Loads a class from a PHP file. The filename must be formatted as "$class.php".
     *
     * include() is not prefixed with the @ operator because if the file is loaded and
     * contains a parse error, execution will halt silently and this is difficult to debug.
     *
     * @param string $class
     * @return bool
     * @throws Exception
     */
    protected static function loadClass(string $class): bool
    {
        if (class_exists($class, false) || interface_exists($class, false)) {
            return false;
        }

        if (preg_match('/[^a-z0-9\\/\\\\_.-]/i', $class)) {
            throw new Exception("illegal characters in classname '".$class."'");
        }

        $file = __DIR__.DIRECTORY_SEPARATOR.self::getFilePath($class).'.php';

        if (! file_exists($file) || ! is_readable($file)) {
            throw new Exception("file '".$file."' does not exist or is not readable");
        }

        if (class_exists($class, false) || interface_exists($class, false)) {
            throw new Exception("class '".$class."' does not exist");
        }

        return include_once $file;
    }

    /**
     * Generates a possible file path for $name.
     *
     * @param string $name
     * @return string
     */
    protected static function getFilePath(string $name): string
    {
        $path = str_replace('_', DIRECTORY_SEPARATOR, $name);

        return str_replace(__CLASS__, dirname(__FILE__), $path);
    }

    /**
     * Checks for required PHP features, enables autoloading, and starts a default profiler.
     *
     * @return void
     * @throws Exception
     */
    public static function init(): void
    {
        if (version_compare(phpversion(), '8.2.0', '>=') == false) {
            throw new Exception('this particular software cannot be used with the installed version of PHP');
        }

        if (! function_exists('stream_socket_client')) {
            throw new Exception('network functions are not available in this PHP installation');
        }

        if (! function_exists('spl_autoload_register')) {
            throw new Exception('autoload functions are not available in this PHP installation');
        }

        Profiler::start();
    }

    /**
     * Returns an assoc array containing all escape patterns available on a TeamSpeak 3 Server
     *
     * @return array
     */
    public static function getEscapePatterns(): array
    {
        return self::$escape_patterns;
    }

    /**
     * Debug helper function. This is a wrapper for var_dump() that adds pre-format tags,
     * cleans up newlines and indents, and runs htmlentities() before output.
     *
     * @param mixed $var
     * @param bool $echo
     * @return string
     */
    public static function dump(mixed $var, bool $echo = true): string
    {
        ob_start();
        var_dump($var);

        $output = preg_replace("/]=>\n(\s+)/m", '] => ', ob_get_clean());

        if (PHP_SAPI == 'cli') {
            $output = PHP_EOL.PHP_EOL.$output.PHP_EOL;
        } else {
            $output = '<pre>'.htmlspecialchars($output, ENT_QUOTES, 'utf-8').'</pre>';
        }

        if ($echo) {
            echo $output;
        }

        return $output;
    }

    public static function encodeArgs(array $props): string
    {
        $parts = [];

        foreach ($props as $key => $val) {
            // bool -> int, null -> empty string
            if (is_bool($val)) {
                $val = $val ? '1' : '0';
            } elseif ($val === null) {
                $val = '';
            } else {
                $val = (string) $val;
            }

            // TeamSpeak3 Query escaping (minimal, robust)
            // ersetzt: Backslash, Pipe, Space
            $val = str_replace(
                ['\\', '|', ' '],
                ['\\\\', '\\p', '\\s'],
                $val
            );

            $parts[] = $key.'='.$val;
        }

        return implode(' ', $parts);
    }
}
