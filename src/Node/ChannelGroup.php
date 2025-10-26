<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Node;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

/**
 * Class ChannelGroup
 * @class ChannelGroup
 * @brief Class describing a TeamSpeak 3-channel group and all it's parameters.
 */
class ChannelGroup extends Group
{
    /**
     * ChannelGroup constructor.
     *
     * @param Server $server
     * @param array $info
     * @param string $index
     * @throws ServerQueryException
     */
    public function __construct(Server $server, array $info, string $index = 'cgid')
    {
        $this->parent = $server;
        $this->nodeInfo = $info;

        if (! array_key_exists($index, $this->nodeInfo)) {
            throw new ServerQueryException('invalid groupID', 0xA00);
        }

        $this->nodeId = $this->nodeInfo[$index];
    }

    /**
     * Renames the channel group specified.
     *
     * @param  string  $name
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function rename(string $name): void
    {
        $this->getParent()->channelGroupRename($this->getId(), $name);
    }

    /**
     * Deletes the channel group. If $force is set to TRUE, the channel group will be
     * deleted even if there are clients within.
     *
     * @param  bool  $force
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function delete(bool $force = false): void
    {
        $this->getParent()->channelGroupDelete($this->getId(), $force);
    }

    /**
     * Creates a copy of the channel group and returns the new groups ID.
     *
     * @param  string|null  $name
     * @param  int  $tcgid
     * @param  int  $type
     * @return int
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function copy(string $name = null, int $tcgid = 0, int $type = TeamSpeak3::GROUP_DBTYPE_REGULAR): int
    {
        return $this->getParent()->channelGroupCopy($this->getId(), $name, $tcgid, $type);
    }

    /**
     * Returns a list of permissions assigned to the channel group.
     *
     * @param  bool  $permsid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permList(bool $permsid = false): array
    {
        return $this->getParent()->channelGroupPermList($this->getId(), $permsid);
    }

    /**
     * Adds a set of specified permissions to the channel group. Multiple permissions
     * can be added by providing the two parameters of each permission in separate arrays.
     *
     * @param  int|array  $permid
     * @param  int|array  $permvalue
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permAssign(int|array $permid, int|array $permvalue): void
    {
        $this->getParent()->channelGroupPermAssign($this->getId(), $permid, $permvalue);
    }

    /**
     * Removes a set of specified permissions from the channel group. Multiple
     * permissions can be removed at once.
     *
     * @param  int|array  $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permRemove(int|array $permid): void
    {
        $this->getParent()->channelGroupPermRemove($this->getId(), $permid);
    }

    /**
     * Returns a list of clients assigned to the channel group specified.
     *
     * @param  int|null  $cid
     * @param  int|null  $cldbid
     * @param  bool  $resolve
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientList(int $cid = null, int $cldbid = null, bool $resolve = false): array
    {
        return $this->getParent()->channelGroupClientList($this->getId(), $cid, $cldbid, $resolve);
    }

    /**
     * Creates a new privilege key (token) for the channel group and returns the key.
     *
     * @param  int  $cid
     * @param  string|null  $description
     * @param  string|null  $customset
     * @return StringHelper
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function privilegeKeyCreate(int $cid, string $description = null, string $customset = null): StringHelper
    {
        return $this->getParent()->privilegeKeyCreate($this->getId(), TeamSpeak3::TOKEN_CHANNELGROUP, $cid, $description, $customset);
    }

    /**
     * @ignore
     * @throws
     */
    protected function fetchNodeList(): void
    {
        $this->nodeList = [];

        foreach ($this->getParent()->clientList() as $client) {
            if ($client['client_channel_group_id'] == $this->getId()) {
                $this->nodeList[] = $client;
            }
        }
    }

    /**
     * Returns the name of a possible icon to display the node object.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return 'group_channel';
    }
}
