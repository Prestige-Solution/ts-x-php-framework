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
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\FileTransferException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\HelperException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

/**
 * Class Channel
 * @class Channel
 * @brief Class describing a TeamSpeak 3 channel and all it's parameters.
 */
class Channel extends Node
{
    private array|null $clientList = null;

    private array $channelList = [];

    /**
     * Channel constructor.
     *
     * @param Server $server
     * @param array $info
     * @param string $index
     * @throws ServerQueryException
     */
    public function __construct(Server $server, array $info, string $index = 'cid')
    {
        $this->parent = $server;
        $this->nodeInfo = $info;

        if (! array_key_exists($index, $this->nodeInfo)) {
            throw new ServerQueryException('invalid channelID', 0x300);
        }

        $this->nodeId = $this->nodeInfo[$index];
    }

    /**
     * Returns an array filled with PlanetTeamSpeak\TeamSpeak3Framework\Node\Channel objects.
     *
     * @param  array  $filter
     * @return array|Channel[]
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function subChannelList(array $filter = []): array
    {
        $channels = [];

        foreach ($this->getParent()->channelList() as $channel) {
            if ($channel['pid'] == $this->getId()) {
                $channels[$channel->getId()] = $channel;
            }
        }

        return $this->filterList($channels, $filter);
    }

    /**
     * Returns the PlanetTeamSpeak\TeamSpeak3Framework\Node\Channel object matching the given ID.
     *
     * @param  int  $cid
     * @return Channel
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function subChannelGetById(int $cid): self
    {
        if (! array_key_exists($cid, $this->subChannelList())) {
            throw new ServerQueryException('invalid channelID', 0x300);
        }

        return $this->channelList[$cid];
    }

    /**
     * Returns the PlanetTeamSpeak\TeamSpeak3Framework\Node\Channel object matching the given name.
     *
     * @param  int  $name
     * @return Channel
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function subChannelGetByName(int $name): self
    {
        foreach ($this->subChannelList() as $channel) {
            if ($channel['channel_name'] == $name) {
                return $channel;
            }
        }

        throw new ServerQueryException('invalid channelID', 0x300);
    }

    /**
     * Returns an array filled with PlanetTeamSpeak\TeamSpeak3Framework\Node\Client objects.
     *
     * @param  array  $filter
     * @return array | Client[]
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientList(array $filter = []): array
    {
        $clients = [];

        foreach ($this->getParent()->clientList() as $client) {
            if ($client['cid'] == $this->getId()) {
                $clients[$client->getId()] = $client;
            }
        }

        return $this->filterList($clients, $filter);
    }

    /**
     * Returns the PlanetTeamSpeak\TeamSpeak3Framework\Node\Client object matching the given ID.
     *
     * @param  int  $clid
     * @return Client
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientGetById(int $clid): Client
    {
        if (! array_key_exists($clid, $this->clientList())) {
            throw new ServerQueryException('invalid clientID', 0x200);
        }

        return $this->clientList[$clid];
    }

    /**
     * Returns the PlanetTeamSpeak\TeamSpeak3Framework\Node\Client object matching the given name.
     *
     * @param  int  $name
     * @return Client
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientGetByName(int $name): Client
    {
        foreach ($this->clientList() as $client) {
            if ($client['client_nickname'] == $name) {
                return $client;
            }
        }

        throw new ServerQueryException('invalid clientID', 0x200);
    }

    /**
     * Returns a list of permissions defined for a client in the channel.
     *
     * @param  int  $cldbid
     * @param  bool  $permsid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientPermList(int $cldbid, bool $permsid = false): array
    {
        return $this->getParent()->channelClientPermList($this->getId(), $cldbid, $permsid);
    }

    /**
     * Adds a set of specified permissions to a client in a specific channel. Multiple permissions can be added by
     * providing the two parameters of each permission.
     *
     * @param  int  $cldbid
     * @param  int|int[]  $permid
     * @param  int|int[]  $permvalue
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientPermAssign(int $cldbid, int|array $permid, int|array $permvalue): void
    {
        $this->getParent()->channelClientPermAssign($this->getId(), $cldbid, $permid, $permvalue);
    }

    /**
     * Alias for clientPermAssign().
     *
     * @deprecated
     * @throws
     */
    public function clientPermAssignByName($cldbid, $permname, $permvalue): void
    {
        $this->clientPermAssign($cldbid, $permname, $permvalue);
    }

    /**
     * Removes a set of specified permissions from a client in the channel. Multiple permissions can be removed at once.
     *
     * @param  int  $cldbid
     * @param  int|int[]  $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function clientPermRemove(int $cldbid, int|array $permid): void
    {
        $this->getParent()->channelClientPermRemove($this->getId(), $cldbid, $permid);
    }

    /**
     * Alias for clientPermRemove().
     *
     * @deprecated
     * @throws
     */
    public function clientPermRemoveByName($cldbid, $permname): void
    {
        $this->clientPermRemove($cldbid, $permname);
    }

    /**
     * Returns a list of permissions defined for the channel.
     *
     * @param  bool  $permsid
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permList(bool $permsid = false): array
    {
        return $this->getParent()->channelPermList($this->getId(), $permsid);
    }

    /**
     * Adds a set of specified permissions to the channel. Multiple permissions can be added by
     * providing the two parameters of each permission.
     *
     * @param  int|int[]  $permid
     * @param  int|int[]  $permvalue
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permAssign(int|array $permid, int|array $permvalue): void
    {
        $this->getParent()->channelPermAssign($this->getId(), $permid, $permvalue);
    }

    /**
     * Alias for permAssign().
     *
     * @deprecated
     * @throws
     */
    public function permAssignByName($permname, $permvalue): void
    {
        $this->permAssign($permname, $permvalue);
    }

    /**
     * Removes a set of specified permissions from the channel. Multiple permissions can be removed at once.
     *
     * @param  int|int[]  $permid
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function permRemove(int|array $permid): void
    {
        $this->getParent()->channelPermRemove($this->getId(), $permid);
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
     * Returns a list of files and directories stored in the channels file repository.
     *
     * @param  string  $cpw
     * @param  string  $path
     * @param  bool  $recursive
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function fileList(string $cpw = '', string $path = '/', bool $recursive = false): array
    {
        return $this->getParent()->channelFileList($this->getId(), $cpw, $path, $recursive);
    }

    /**
     * Returns detailed information about the specified file stored in the channels file repository.
     *
     * @param  string  $cpw
     * @param  string  $name
     * @return array
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function fileInfo(string $cpw = '', string $name = '/'): array
    {
        return $this->getParent()->channelFileInfo($this->getId(), $cpw, $name);
    }

    /**
     * Renames a file in the channels file repository. If the two parameters $tcid and $tcpw are specified, the file
     * will be moved into another channels file repository.
     *
     * @param  string  $cpw
     * @param  string  $oldname
     * @param  string  $newname
     * @param  int|null  $tcid
     * @param  string|null  $tcpw
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function fileRename(string $cpw = '', string $oldname = '/', string $newname = '/', int $tcid = null, string $tcpw = null): void
    {
        $this->getParent()->channelFileRename($this->getId(), $cpw, $oldname, $newname, $tcid, $tcpw);
    }

    /**
     * Deletes one or more files stored in the channels file repository.
     *
     * @param  string  $cpw
     * @param  string  $name
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function fileDelete(string $cpw = '', string $name = '/'): void
    {
        $this->getParent()->channelFileDelete($this->getId(), $cpw, $name);
    }

    /**
     * Creates new directory in a channels file repository.
     *
     * @param  string  $cpw
     * @param  string  $dirname
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function dirCreate(string $cpw = '', string $dirname = '/'): void
    {
        $this->getParent()->channelDirCreate($this->getId(), $cpw, $dirname);
    }

    /**
     * Returns the level of the channel.
     *
     * @return int
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function getLevel(): int
    {
        return $this->getParent()->channelGetLevel($this->getId());
    }

    /**
     * Returns the pathway of the channel which can be used as a clients default channel.
     *
     * @return string
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function getPathway(): string
    {
        return $this->getParent()->channelGetPathway($this->getId());
    }

    /**
     * Returns the possible spacer type of the channel.
     *
     * @return int
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function spacerGetType(): int
    {
        return $this->getParent()->channelSpacerGetType($this->getId());
    }

    /**
     * Returns the possible spacer alignment of the channel.
     *
     * @return int
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function spacerGetAlign(): int
    {
        return $this->getParent()->channelSpacerGetAlign($this->getId());
    }

    /**
     * Returns TRUE if the channel is a spacer.
     *
     * @return bool
     */
    public function isSpacer(): bool
    {
        return $this->getParent()->channelIsSpacer($this);
    }

    /**
     * Downloads and returns the channels icon file content.
     *
     * @return StringHelper|void
     * @throws AdapterException
     * @throws HelperException
     * @throws ServerQueryException
     * @throws FileTransferException
     * @throws TransportException
     */
    public function iconDownload()
    {
        $iconid = $this['channel_icon_id'];
        if (! is_int($iconid)) {
            $iconid = $iconid->toInt();
        }

        if ($this->iconIsLocal('channel_icon_id') || $iconid == 0) {
            return;
        }

        $download = $this->getParent()->transferInitDownload(rand(0x0000, 0xFFFF), 0, $this->iconGetName('channel_icon_id'));
        $transfer = TeamSpeak3::factory('filetransfer://'.(str_contains($download['host'], ':') ? '['.$download['host'].']' : $download['host']).':'.$download['port']);

        return $transfer->download($download['ftkey'], $download['size']);
    }

    /**
     * Changes the channel configuration using given properties.
     *
     * @param  array  $properties
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function modify(array $properties): void
    {
        $properties['cid'] = $this->getId();

        $this->execute('channeledit', $properties);
        $this->resetNodeInfo();
    }

    /**
     * Sends a text message to all clients in the channel.
     *
     * @param  string  $msg
     * @param  string|null  $cpw
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function message(string $msg, string $cpw = null): void
    {
        if ($this->getId() != $this->getParent()->whoamiGet('client_channel_id')) {
            $this->getParent()->clientMove($this->getParent()->whoamiGet('client_id'), $this->getId(), $cpw);
        }

        $this->execute('sendtextmessage', ['msg' => $msg, 'target' => $this->getId(), 'targetmode' => TeamSpeak3::TEXTMSG_CHANNEL]);
    }

    /**
     * Deletes the channel.
     *
     * @param  bool  $force
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function delete(bool $force = false): void
    {
        $this->getParent()->channelDelete($this->getId(), $force);
    }

    /**
     * Moves the channel to the parent channel specified with $pid.
     *
     * @param  int  $pid
     * @param  int|null  $order
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function move(int $pid, int $order = null): void
    {
        $this->getParent()->channelMove($this->getId(), $pid, $order);
    }

    /**
     * Sends a plugin command to all clients in the channel.
     *
     * @param  string  $plugin
     * @param  string  $data
     * @param  string|null  $cpw
     * @param  bool  $subscribed
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function sendPluginCmd(string $plugin, string $data, string $cpw = null, bool $subscribed = false): void
    {
        if ($this->getId() != $this->getParent()->whoamiGet('client_channel_id')) {
            $this->getParent()->clientMove($this->getParent()->whoamiGet('client_id'), $this->getId(), $cpw);
        }

        $this->execute('plugincmd', ['name' => $plugin, 'data' => $data, 'targetmode' => $subscribed ? TeamSpeak3::PLUGINCMD_CHANNEL_SUBSCRIBED : TeamSpeak3::PLUGINCMD_CHANNEL]);
    }

    /**
     * @ignore
     * @throws
     */
    protected function fetchNodeList(): void
    {
        $this->nodeList = [];

        if ($this->getParent()->getLoadClientlistFirst()) {
            foreach ($this->clientList() as $client) {
                if ($client['cid'] == $this->getId()) {
                    $this->nodeList[] = $client;
                }
            }

            foreach ($this->subChannelList() as $channel) {
                if ($channel['pid'] == $this->getId()) {
                    $this->nodeList[] = $channel;
                }
            }
        } else {
            foreach ($this->subChannelList() as $channel) {
                if ($channel['pid'] == $this->getId()) {
                    $this->nodeList[] = $channel;
                }
            }

            foreach ($this->clientList() as $client) {
                if ($client['cid'] == $this->getId()) {
                    $this->nodeList[] = $client;
                }
            }
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
        $this->nodeInfo = array_merge($this->nodeInfo, $this->execute('channelinfo', ['cid' => $this->getId()])->toList());
    }

    /**
     * Returns a unique identifier for the node which can be used as an HTML property.
     *
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->getParent()->getUniqueId().'_ch'.$this->getId();
    }

    /**
     * Returns the name of a possible icon to display the node object.
     *
     * @return string
     */
    public function getIcon(): string
    {
        if (! $this['channel_maxclients'] || ($this['channel_maxclients'] != -1 && $this['channel_maxclients'] <= $this['total_clients'])) {
            return 'channel_full';
        } elseif ($this['channel_flag_password']) {
            return 'channel_pass';
        } else {
            return 'channel_open';
        }
    }

    /**
     * Returns a symbol representing the node.
     *
     * @return string
     */
    public function getSymbol(): string
    {
        return '#';
    }

    /**
     * Returns a string representation of this node.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this['channel_name'];
    }
}
