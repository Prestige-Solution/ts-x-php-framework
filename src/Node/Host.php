<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Node;

use PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Convert;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Crypt;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;
use ReflectionClass;

/**
 * Class Host
 * @class Host
 * @brief Class describing a TeamSpeak 3 server instance and all it's parameters.
 */
class Host extends Node
{
    protected array|null $whoami = null;

    protected array|null $version = null;

    protected array|null $serverList = null;

    protected array|null $permissionEnds = null;

    protected array|null $permissionList = null;

    protected array|null $permissionCats = null;

    protected string|null $predefined_query_name = null;

    protected bool $exclude_query_clients = false;

    protected bool $start_offline_virtual = false;

    protected bool $sort_clients_channels = false;

    public function __construct(ServerQuery $squery)
    {
        $this->parent = $squery;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverSelectedId(): int
    {
        return $this->whoamiGet('virtualserver_id', 0);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverSelectedPort(): int
    {
        return $this->whoamiGet('virtualserver_port', 0);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function version(string $ident = null): mixed
    {
        if ($this->version === null) {
            $this->version = $this->request('version')->toList();
        }

        return ($ident && isset($this->version[$ident])) ? $this->version[$ident] : $this->version;
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverSelect(int $sid, bool $virtual = null): void
    {
        if ($this->whoami !== null && $this->serverSelectedId() === $sid) {
            return;
        }

        $virtual = $virtual ?? $this->start_offline_virtual;
        $getargs = func_get_args();

        $args = ['sid' => $sid];
        if ($sid !== 0 && $this->predefined_query_name) {
            $args['client_nickname'] = (string) $this->predefined_query_name;
        }
        if ($virtual) {
            $args['-virtual'] = null;
        }

        $this->execute('use', $args);
        $this->whoamiReset();

        if ($sid !== 0 && $this->predefined_query_name && $this->whoamiGet('client_nickname') !== $this->predefined_query_name) {
            $this->execute('clientupdate', ['client_nickname' => (string) $this->predefined_query_name]);
        }

        $this->setStorage('_server_use', [__FUNCTION__, $getargs]);
        Signal::getInstance()->emit('notifyServerselected', $this);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverSelectById(int $sid, bool $virtual = null): void
    {
        $this->serverSelect($sid, $virtual);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverSelectByPort(int $port, bool $virtual = null): void
    {
        if ($this->whoami !== null && $this->serverSelectedPort() === $port) {
            return;
        }

        $virtual = $virtual ?? $this->start_offline_virtual;
        $getargs = func_get_args();

        $args = ['port' => $port];
        if ($port !== 0 && $this->predefined_query_name) {
            $args['client_nickname'] = (string) $this->predefined_query_name;
        }
        if ($virtual) {
            $args['-virtual'] = null;
        }

        $this->execute('use', $args);
        $this->whoamiReset();

        if ($port !== 0 && $this->predefined_query_name && $this->whoamiGet('client_nickname') !== $this->predefined_query_name) {
            $this->execute('clientupdate', ['client_nickname' => (string) $this->predefined_query_name]);
        }

        $this->setStorage('_server_use', [__FUNCTION__, $getargs]);
        Signal::getInstance()->emit('notifyServerselected', $this);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverDeselect(): void
    {
        $this->serverSelect(0);
        $this->delStorage('_server_use');
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverIdGetByPort(int $port): int
    {
        $sid = $this->execute('serveridgetbyport', ['virtualserver_port' => $port])->toList();

        return $sid['server_id'];
    }

    /**
     * @param  int  $sid
     * @return int
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverGetPortById(int $sid): int
    {
        if (! isset($this->serverList()[$sid])) {
            throw new ServerQueryException('invalid serverID', 0x400);
        }

        return $this->serverList()[$sid]['virtualserver_port'];
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverGetSelected(): Server
    {
        return $this->serverGetById($this->serverSelectedId());
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverGetById(int $sid): Server
    {
        $this->serverSelectById($sid);

        return new Server($this, ['virtualserver_id' => $sid]);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverGetByPort(int $port): Server
    {
        $this->serverSelectByPort($port);

        return new Server($this, ['virtualserver_id' => $this->serverSelectedId()]);
    }

    /**
     * @param  string  $name
     * @return Server
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverGetByName(string $name): Server
    {
        foreach ($this->serverList() as $server) {
            if ($server['virtualserver_name'] === $name) {
                return $server;
            }
        }
        throw new ServerQueryException('invalid serverID', 0x400);
    }

    /**
     * @param  string  $uid
     * @return Server
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverGetByUid(string $uid): Server
    {
        foreach ($this->serverList() as $server) {
            if ($server['virtualserver_unique_identifier'] === $uid) {
                return $server;
            }
        }
        throw new ServerQueryException('invalid serverID', 0x400);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverCreate(array $properties = []): array
    {
        $this->serverListReset();
        $detail = $this->execute('servercreate', $properties)->toList();
        $server = new Server($this, ['virtualserver_id' => (int) $detail['sid']]);

        Signal::getInstance()->emit('notifyServercreated', $this, $detail['sid']);
        Signal::getInstance()->emit('notifyTokencreated', $server, $detail['token']);

        return $detail;
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverDelete(int $sid): void
    {
        if ($sid === $this->serverSelectedId()) {
            $this->serverDeselect();
        }
        $this->execute('serverdelete', ['sid' => $sid]);
        $this->serverListReset();
        Signal::getInstance()->emit('notifyServerdeleted', $this, $sid);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverStart(int $sid): void
    {
        if ($sid === $this->serverSelectedId()) {
            $this->serverDeselect();
        }
        $this->execute('serverstart', ['sid' => $sid]);
        $this->serverListReset();
        Signal::getInstance()->emit('notifyServerstarted', $this, $sid);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverStop(int $sid, string $msg = null): void
    {
        if ($sid === $this->serverSelectedId()) {
            $this->serverDeselect();
        }
        $this->execute('serverstop', ['sid' => $sid, 'reasonmsg' => $msg]);
        $this->serverListReset();
        Signal::getInstance()->emit('notifyServerstopped', $this, $sid);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function serverStopProcess(string $msg = null): void
    {
        Signal::getInstance()->emit('notifyServershutdown', $this);
        $this->execute('serverprocessstop', ['reasonmsg' => $msg]);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverList(array $filter = []): array|Server
    {
        if ($this->serverList === null) {
            $servers = $this->request('serverlist -uid')->toAssocArray('virtualserver_id');
            $this->serverList = [];
            foreach ($servers as $sid => $server) {
                $this->serverList[$sid] = new Server($this, $server);
            }
            $this->resetNodeList();
        }

        return $this->filterList($this->serverList, $filter);
    }

    public function serverListReset(): void
    {
        $this->resetNodeList();
        $this->serverList = null;
    }

    /**
     * Returns a list of IP addresses used by the server instance on multi-homed machines.
     *
     * @param  string  $subsystem
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function bindingList(string $subsystem = 'voice'): array
    {
        return $this->execute('bindinglist', ['subsystem' => $subsystem])->toArray();
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function apiKeyCount(): int
    {
        return current($this->execute('apikeylist -count', ['duration' => 1])->toList());
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function apiKeyList(int $offset = null, int $limit = null, mixed $cldbid = null): array
    {
        return $this->execute('apikeylist -count', ['start' => $offset, 'duration' => $limit, 'cldbid' => $cldbid])->toAssocArray('id');
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function apiKeyCreate(string $scope = TeamSpeak3::APIKEY_READ, int $lifetime = 14, int $cldbid = null): array
    {
        $detail = $this->execute('apikeyadd', ['scope' => $scope, 'lifetime' => $lifetime, 'cldbid' => $cldbid])->toList();
        Signal::getInstance()->emit('notifyApikeycreated', $this, $detail['apikey']);

        return $detail;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function apiKeyDelete(int $id): void
    {
        $this->execute('apikeydel', ['id' => $id]);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function permissionList(): array
    {
        if ($this->permissionList === null) {
            $this->fetchPermissionList();
        }
        foreach ($this->permissionList as $permname => $permdata) {
            $this->permissionList[$permname]['permcatid'] ??= $this->permissionGetCategoryById($permdata['permid']);
            $this->permissionList[$permname]['permgrant'] ??= $this->permissionGetGrantById($permdata['permid']);
        }

        return $this->permissionList;
    }

    public function permissionCats(): array
    {
        if ($this->permissionCats === null) {
            $this->fetchPermissionCats();
        }

        return $this->permissionCats;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permissionEnds(): array
    {
        if ($this->permissionEnds === null) {
            $this->fetchPermissionList();
        }

        return $this->permissionCats;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permissionTree(): array
    {
        $permtree = [];
        $permissions = $this->permissionList();
        foreach ($this->permissionCats() as $val) {
            $permtree[$val] = [
                'permcatid' => $val,
                'permcathex' => '0x'.dechex($val),
                'permcatname' => StringHelper::factory(Convert::permissionCategory($val)),
                'permcatparent' => 0,
                'permcatchilren' => 0,
                'permcatcount' => 0,
            ];
            foreach ($permissions as $perm) {
                if ($perm['permcatid'] === $val) {
                    $permtree[$val]['permcatcount']++;
                }
            }
        }

        return $permtree;
    }

    /**
     * Returns the IDs of all clients, channels or groups using the permission with the
     * specified ID.
     *
     * @param  int|int[]  $permissionId
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permissionFind(int|array $permissionId): array
    {
        if (! is_array($permissionId)) {
            $permident = (is_numeric($permissionId)) ? 'permid' : 'permsid';
        } else {
            $permident = (is_numeric(current($permissionId))) ? 'permid' : 'permsid';
        }

        return $this->execute('permfind', [$permident => $permissionId])->toArray();
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function permissionGetIdByName(string $name): int
    {
        if (! isset($this->permissionList()[$name])) {
            throw new ServerQueryException('invalid permission ID', 0xA02);
        }

        return $this->permissionList()[$name]['permid'];
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function permissionGetNameById(int $permissionId): StringHelper
    {
        foreach ($this->permissionList() as $name => $perm) {
            if ($perm['permid'] === $permissionId) {
                return new StringHelper($name);
            }
        }
        throw new ServerQueryException('invalid permission ID', 0xA02);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permissionGetCategoryById(int $permid): int
    {
        if ($permid < 0x1000) {
            if ($this->permissionEnds === null) {
                $this->fetchPermissionList();
            }
            if ($this->permissionCats === null) {
                $this->fetchPermissionCats();
            }
            foreach (array_values($this->permissionCats) as $key => $val) {
                if ($this->permissionEnds[$key] >= $permid) {
                    return $val;
                }
            }

            return 0;
        }

        return $permid >> 8;
    }

    public function permissionGetGrantById(int $permid): int
    {
        return ($permid < 0x1000) ? $permid + 0x8000 : bindec(substr(decbin($permid), -8)) + 0xFF00;
    }

    /**
     * Adds a set of specified permissions to all regular server groups on all virtual servers. The target groups will
     * be identified by the value of their i_group_auto_update_type permission specified with $sgtype.
     *
     * @param  int  $sgtype
     * @param  int|int[]  $permid
     * @param  int|int[]  $permvalue
     * @param  int|int[]  $permnegated
     * @param  bool|bool[]  $permskip
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverGroupPermAutoAssign(int $sgtype, int|array $permid, int|array $permvalue, int|array $permnegated = 0, array|bool $permskip = false): void
    {
        if (! is_array($permid)) {
            $permident = (is_numeric($permid)) ? 'permid' : 'permsid';
        } else {
            $permident = (is_numeric(current($permid))) ? 'permid' : 'permsid';
        }

        $this->execute('servergroupautoaddperm', ['sgtype' => $sgtype, $permident => $permid, 'permvalue' => $permvalue, 'permnegated' => $permnegated, 'permskip' => $permskip]);
    }

    /**
     * Removes a set of specified permissions from all regular server groups on all virtual servers. The target groups
     * will be identified by the value of their i_group_auto_update_type permission specified with $sgtype.
     *
     * @param  int  $sgtype
     * @param  int|int[]  $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function serverGroupPermAutoRemove(int $sgtype, int|array $permid): void
    {
        if (! is_array($permid)) {
            $permident = (is_numeric($permid)) ? 'permid' : 'permsid';
        } else {
            $permident = (is_numeric(current($permid))) ? 'permid' : 'permsid';
        }

        $this->execute('servergroupautodelperm', ['sgtype' => $sgtype, $permident => $permid]);
    }

    /**
     * Returns an array containing the value of a specified permission for your own client.
     *
     * @param  int|int[]  $permid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function selfPermCheck(int|array $permid): array
    {
        if (! is_array($permid)) {
            $permident = (is_numeric($permid)) ? 'permid' : 'permsid';
        } else {
            $permident = (is_numeric(current($permid))) ? 'permid' : 'permsid';
        }

        return $this->execute('permget', [$permident => $permid])->toAssocArray('permsid');
    }

    /**
     * Changes the server instance configuration using given properties.
     *
     * @param  array  $properties
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function modify(array $properties): void
    {
        $this->execute('instanceedit', $properties);
        $this->resetNodeInfo();
    }

    /**
     * Sends a text message to all clients on all virtual servers in the TeamSpeak 3 Server instance.
     *
     * @param  string  $msg
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function message(string $msg): void
    {
        $this->execute('gm', ['msg' => $msg]);
    }

    /**
     * Displays a specified number of entries (1-100) from the server log.
     *
     * @param  int  $lines
     * @param  int|null  $begin_pos
     * @param  bool|null  $reverse
     * @param  bool  $instance
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function logView(int $lines = 30, int $begin_pos = null, bool $reverse = null, bool $instance = true): array
    {
        return $this->execute('logview', ['lines' => $lines, 'begin_pos' => $begin_pos, 'instance' => $instance, 'reverse' => $reverse])->toArray();
    }

    /**
     * Writes a custom entry into the server instance log.
     *
     * @param  string  $logmsg
     * @param  int  $loglevel
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function logAdd(string $logmsg, int $loglevel = TeamSpeak3::LOGLEVEL_INFO): void
    {
        $sid = $this->serverSelectedId();

        $this->serverDeselect();
        $this->execute('logadd', ['logmsg' => $logmsg, 'loglevel' => $loglevel]);
        $this->serverSelect($sid);
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     * @throws HelperException
     */
    public function login(string $username, string $password): void
    {
        $this->execute('login', ['client_login_name' => $username, 'client_login_password' => $password]);
        $this->whoamiReset();

        if ($this->predefined_query_name) {
            $clients = $this->request('clientlist -uid')->toList();

            foreach ($clients as $client) {
                if ($client['client_nickname'] === $this->predefined_query_name) {
                    // Kick old query with same nickname
                    $this->execute('clientkick', [
                        'clid'      => $client['clid'],
                        'reasonid'  => 5,
                        'reasonmsg' => 'Replaced by new query session',
                    ]);
                }
            }

            // Set the nickname for the current session now
            $this->execute('clientupdate', [
                'client_nickname' => (string) $this->predefined_query_name,
            ]);
        }

        $crypt = new Crypt($username);
        $this->setStorage('_login_user', $username);
        $this->setStorage('_login_pass', $crypt->encrypt($password));

        Signal::getInstance()->emit('notifyLogin', $this);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function logout(): void
    {
        $this->request('logout');
        $this->whoamiReset();

        $this->delStorage('_login_user');
        $this->delStorage('_login_pass');

        Signal::getInstance()->emit('notifyLogout', $this);
    }

    /**
     * Returns the number of ServerQuery logins on the selected virtual server.
     *
     * @param  string|null  $pattern
     * @return mixed
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function queryCountLogin(string $pattern = null): mixed
    {
        return current($this->execute('queryloginlist -count', ['duration' => 1, 'pattern' => $pattern])->toList());
    }

    /**
     * Returns a list of ServerQuery logins on the selected virtual server. By default, the server spits out 25 entries
     * at once.
     *
     * @param  int|null  $offset
     * @param  int|null  $limit
     * @param  string|null  $pattern
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function queryListLogin(int $offset = null, int $limit = null, string $pattern = null): array
    {
        return $this->execute('queryloginlist -count', ['start' => $offset, 'duration' => $limit, 'pattern' => $pattern])->toAssocArray('cldbid');
    }

    /**
     * Creates a new ServerQuery login or enables ServerQuery logins for an existing client. When no virtual server is
     * selected, the command will create a global ServerQuery login, otherwise a ServerQuery login will be added for an
     * existing client (cldbid must be specified).
     *
     * @param  string  $username
     * @param  int  $cldbid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function queryLoginCreate(string $username, int $cldbid = 0): array
    {
        if ($this->serverSelectedId()) {
            return $this->execute('queryloginadd', ['client_login_name' => $username, 'cldbid' => $cldbid])->toList();
        } else {
            return $this->execute('queryloginadd', ['client_login_name' => $username])->toList();
        }
    }

    /**
     * Deletes an existing ServerQuery login.
     *
     * @param  int  $cldbid
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function queryLoginDelete(int $cldbid): void
    {
        $this->execute('querylogindel', ['cldbid' => $cldbid]);
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     */
    public function whoami(): array
    {
        // Execute server request
        $response = $this->request('whoami')->toList();

        // response[1] contains the actual data
        $data = $response[1] ?? [];

        // Automatically convert StringHelper to strings
        foreach ($data as $key => $val) {
            if ($val instanceof StringHelper) {
                $data[$key] = $val->toString();
            }
        }

        // Set cache and return
        return $this->whoami = $data;
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function whoamiGet(string $ident, mixed $default = null): mixed
    {
        return $this->whoami()[$ident] ?? $default;
    }

    /**
     * @throws TransportException
     * @throws ServerQueryException
     * @throws AdapterException
     */
    public function whoamiSet(string $ident, mixed $value = null): void
    {
        // If it is the client_channel_id → move it to the server
        if ($ident === 'client_channel_id') {
            $cid = (int) $value;
            $this->execute('clientmove', [
                'clid' => $this->whoami()['client_id'],
                'cid'  => $cid,
            ]);
            $this->whoami(); // reload
        } else {
            // fallback: set only the local cache
            $this->whoami();
            $this->whoami[$ident] = is_numeric($value) ? (int) $value : StringHelper::factory($value);
        }
    }

    public function whoamiReset(): void
    {
        $this->whoami = null;
    }

    public function getAdapterHost(): string
    {
        return $this->getParent()->getTransportHost();
    }

    public function getAdapterPort(): string
    {
        return $this->getParent()->getTransportPort();
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     * @ignore
     */
    protected function fetchNodeList(): void
    {
        $servers = $this->serverList();

        foreach ($servers as $server) {
            $this->nodeList[] = $server;
        }
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     * @ignore
     */
    protected function fetchNodeInfo(): void
    {
        $info1 = $this->request('hostinfo')->toList();
        $info2 = $this->request('instanceinfo')->toList();

        $this->nodeInfo = array_merge($this->nodeInfo, $info1, $info2);
    }

    /**
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     * @ignore
     */
    protected function fetchPermissionList(): void
    {
        $reply = $this->request('permissionlist -new')->toArray();
        $start = 1;

        $this->permissionEnds = [];
        $this->permissionList = [];

        foreach ($reply as $line) {
            if (array_key_exists('group_id_end', $line)) {
                $this->permissionEnds[] = $line['group_id_end'];
            } else {
                $this->permissionList[$line['permname']->toString()] = array_merge(['permid' => $start++], $line);
            }
        }
    }

    /**
     * @ignore
     */
    protected function fetchPermissionCats(): void
    {
        $permcats = [];
        $reflects = new ReflectionClass('TeamSpeak3');

        foreach ($reflects->getConstants() as $key => $val) {
            if (! StringHelper::factory($key)->startsWith('PERM_CAT') || $val == 0xFF) {
                continue;
            }

            $permcats[$key] = $val;
        }

        $this->permissionCats = $permcats;
    }

    public function setPredefinedQueryName(string $name = null): void
    {
        $this->predefined_query_name = $name;
        $this->setStorage('_query_nick', $name);
    }

    public function getPredefinedQueryName(): ?string
    {
        return $this->predefined_query_name;
    }

    public function setExcludeQueryClients(bool $exclude = false): void
    {
        $this->exclude_query_clients = $exclude;
        $this->setStorage('_query_hide', $exclude);
    }

    public function getExcludeQueryClients(): bool
    {
        return $this->exclude_query_clients;
    }

    public function setUseOfflineAsVirtual(bool $virtual = false): void
    {
        $this->start_offline_virtual = $virtual;
        $this->setStorage('_do_virtual', $virtual);
    }

    public function getUseOfflineAsVirtual(): bool
    {
        return $this->start_offline_virtual;
    }

    public function setLoadClientlistFirst(bool $first = false): void
    {
        $this->sort_clients_channels = $first;
        $this->setStorage('_client_top', $first);
    }

    public function getLoadClientlistFirst(): bool
    {
        return $this->sort_clients_channels;
    }

    public function getAdapter(): ServerQuery
    {
        return $this->getParent();
    }

    /**
     * Returns the name of a possible icon to display the node object.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return 'host';
    }

    /**
     * Returns a symbol representing the node.
     *
     * @return string
     */
    public function getSymbol(): string
    {
        return '+';
    }

    /**
     * @throws AdapterException
     * @throws TransportException
     * @throws ServerQueryException
     * @throws HelperException
     */
    public function __wakeup()
    {
        $username = $this->getStorage('_login_user');
        $password = $this->getStorage('_login_pass');

        // Automatic reconnection with saved login details
        if ($username && $password) {
            $crypt = new Crypt($username);
            $this->login($username, $crypt->decrypt($password));
        }

        $this->predefined_query_name = $this->getStorage('_query_nick');
        $this->exclude_query_clients = $this->getStorage('_query_hide', false);
        $this->start_offline_virtual = $this->getStorage('_do_virtual', false);
        $this->sort_clients_channels = $this->getStorage('_client_top', false);

        // If Nick has set it, check whether it is occupied
        if ($this->predefined_query_name) {
            try {
                $clients = $this->request('clientlist -uid')->toList();
                $nickInUse = false;

                foreach ($clients as $client) {
                    if ($client['client_nickname'] === $this->predefined_query_name) {
                        $nickInUse = true;
                        break;
                    }
                }

                $finalNick = $this->predefined_query_name;
                if ($nickInUse) {
                    $finalNick .= '_'.mt_rand(1000, 9999); // Fallback-Suffix
                }

                $this->execute('clientupdate', [
                    'client_nickname' => (string) $finalNick,
                ]);

                $this->predefined_query_name = $finalNick;
            } catch (\Exception $e) {
                // Send signal or log if nick update fails
                Signal::getInstance()->emit('notifyNicknameError', $e->getMessage());
            }
        }

        // Reselect a previously used server
        if ($server = $this->getStorage('_server_use')) {
            $func = array_shift($server);
            $args = array_shift($server);

            if (method_exists($this, $func)) {
                call_user_func_array([$this, $func], (array) $args);
            }
        }
    }

    /**
     * Returns a string representation of this node.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getAdapterHost();
    }
}
