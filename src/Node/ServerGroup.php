<?php

/**
 * @file
 * TeamSpeak 3 PHP Framework
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Sven 'ScP' Paulsen
 * @copyright Copyright (c) Planet TeamSpeak. All rights reserved.
 */

namespace PlanetTeamSpeak\TeamSpeak3Framework\Node;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\NodeException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

/**
 * @class ServerGroup
 * @brief Class describing a TeamSpeak 3 server group and all it's parameters.
 */
class ServerGroup extends Group
{
    /**
     * The ServerGroup constructor.
     *
     * @param Server $server
     * @param array $info
     * @param string $index
     * @throws NodeException
     */
    public function __construct(Server $server, array $info, string $index = 'sgid')
    {
        $this->parent = $server;
        $this->nodeInfo = $info;

        if (! array_key_exists($index, $this->nodeInfo)) {
            throw new NodeException('invalid groupID', 0xA00);
        }

        $this->nodeId = $this->nodeInfo[$index];
    }

    /**
     * Renames the server group specified.
     *
     * @param  string  $name
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function rename(string $name): void
    {
        $this->getParent()->serverGroupRename($this->getId(), $name);
    }

    /**
     * Deletes the server group. If $force is set to 1, the server group will be
     * deleted even if there are clients within.
     *
     * @param  bool  $force
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function delete(bool $force = false): void
    {
        $this->getParent()->serverGroupDelete($this->getId(), $force);
    }

    /**
     * Creates a copy of the server group and returns the new groups ID.
     *
     * @param  string|null  $name
     * @param  int  $tsgid
     * @param  int  $type
     * @return int
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function copy(string $name = null, int $tsgid = 0, int $type = TeamSpeak3::GROUP_DBTYPE_REGULAR): int
    {
        return $this->getParent()->serverGroupCopy($this->getId(), $name, $tsgid, $type);
    }

    /**
     * Returns a list of permissions assigned to the server group.
     *
     * @param  bool  $permsid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function permList(bool $permsid = false): array
    {
        return $this->getParent()->serverGroupPermList($this->getId(), $permsid);
    }

    /**
     * Adds a set of specified permissions to the server group. Multiple permissions
     * can be added by providing the four parameters of each permission in separate arrays.
     *
     * @param  int  $permid
     * @param  int  $permvalue
     * @param  int  $permnegated
     * @param  int  $permskip
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function permAssign(int $permid, int $permvalue, int $permnegated = 0, int $permskip = 0): void
    {
        $this->getParent()->serverGroupPermAssign($this->getId(), $permid, $permvalue, $permnegated, $permskip);
    }

    /**
     * Alias for permAssign().
     *
     * @deprecated
     * @throws
     */
    public function permAssignByName($permname, $permvalue, $permnegated = false, $permskip = false): void
    {
        $this->permAssign($permname, $permvalue, $permnegated, $permskip);
    }

    /**
     * Removes a set of specified permissions from the server group. Multiple
     * permissions can be removed at once.
     *
     * @param  int  $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function permRemove(int $permid): void
    {
        $this->getParent()->serverGroupPermRemove($this->getId(), $permid);
    }

    /**
     * Alias for permRemove().
     *
     * @deprecated
     * @throws
     */
    public function permRemoveByName($permname): void
    {
        $this->permRemove($permname);
    }

    /**
     * Returns a list of clients assigned to the server group specified.
     *
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientList(): array
    {
        return $this->getParent()->serverGroupClientList($this->getId());
    }

    /**
     * Adds a client to the server group specified. Please note that a client cannot be
     * added to default groups or template groups.
     *
     * @param  int  $cldbid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientAdd(int $cldbid): void
    {
        $this->getParent()->serverGroupClientAdd($this->getId(), $cldbid);
    }

    /**
     * Removes a client from the server group.
     *
     * @param  int  $cldbid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function clientDel(int $cldbid): void
    {
        $this->getParent()->serverGroupClientDel($this->getId(), $cldbid);
    }

    /**
     * Alias for privilegeKeyCreate().
     *
     * @deprecated
     * @throws
     */
    public function tokenCreate($description = null, $customset = null): string
    {
        return $this->privilegeKeyCreate($description, $customset);
    }

    /**
     * Creates a new privilege key (token) for the server group and returns the key.
     *
     * @param  string|null  $description
     * @param  string|null  $customset
     * @return string
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function privilegeKeyCreate(string $description = null, string $customset = null): string
    {
        return $this->getParent()
            ->privilegeKeyCreate($this->getId(), TeamSpeak3::TOKEN_SERVERGROUP, 0, $description, $customset);
    }

    /**
     * @ignore
     * @throws
     */
    protected function fetchNodeList(): void
    {
        $this->nodeList = [];

        foreach ($this->getParent()->clientList() as $client) {
            if (in_array($this->getId(), explode(',', $client['client_servergroups']))) {
                $this->nodeList[] = $client;
            }
        }
    }

    /**
     * Returns a unique identifier for the node which can be used as an HTML property.
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->getParent()->getUniqueId().'_sg'.$this->getId();
    }

    /**
     * Returns the name of a possible icon to display the node object.
     *
     * @return string
     */
    public function getIcon(): string
    {
        return 'group_server';
    }
}
