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

namespace PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\Node\Host;
use PlanetTeamSpeak\TeamSpeak3Framework\TeamSpeak3;

/**
 * Class Reply
 * @class Reply
 * @brief Provides methods to analyze and format a ServerQuery reply.
 */
class Reply
{
    /**
     * Stores the command used to get this reply.
     *
     * @var StringHelper
     */
    protected StringHelper $cmd;

    /**
     * Stores the servers reply (if available).
     *
     * @var StringHelper|null
     */
    protected ?StringHelper $rpl = null;

    /**
     * Stores connected PlanetTeamSpeak\TeamSpeak3Framework\Node\Host object.
     *
     * @var Host|null
     */
    protected Host|null $con;

    /**
     * Stores an assoc array containing the error info for this reply.
     *
     * @var array
     */
    protected array $err = [];

    /**
     * Sotres an array of events that occured before or during this reply.
     *
     * @var array
     */
    protected array $evt = [];

    /**
     * Indicates whether exceptions should be thrown or not.
     *
     * @var bool
     */
    protected bool $exp = true;

    /**
     * Creates a new PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Reply object.
     *
     * @param  array  $rpl
     * @param  string  $cmd
     * @param  Host|null  $con
     * @param  bool  $exp
     * @throws AdapterException
     * @throws ServerQueryException
     */
    public function __construct(array $rpl, string $cmd = '', Host $con = null, bool $exp = true)
    {
        $this->cmd = new StringHelper($cmd);
        $this->con = $con;
        $this->exp = $exp;

        $this->fetchError(array_pop($rpl));
        $this->fetchReply($rpl);
    }

    /**
     * Returns the reply as an PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper object.
     *
     * @return StringHelper|null
     */
    public function toString(): ?StringHelper
    {
        //get count of arguments there passed to this function / 0 is similar to !func_num_args() but this variant results in bool(false)
        $stringArgs = func_get_args();
        if ($stringArgs >= 1) {
            return $this->rpl;
        } else {
            return $this->rpl->unescape();
        }
    }

    /**
     * Returns the reply as a standard PHP array where each element represents one item.
     *
     * @return array
     */
    public function toLines(): array
    {
        if (! count($this->rpl)) {
            return [];
        }

        $list = $this->toString()->split(TeamSpeak3::SEPARATOR_LIST);

        //get count of arguments there passed to this function / 0 is similar to !func_num_args() but this variant results in bool(false)
        $linesArgs = func_num_args();
        if ($linesArgs >= 1) {
            for ($i = 0; $i < count($list); $i++) {
                $list[$i]->unescape();
            }
        }

        return $list;
    }

    /**
     * Returns the reply as a standard PHP array where each element represents one item in table format.
     *
     * @return array
     */
    public function toTable(): array
    {
        $table = [];

        foreach ($this->toLines() as $cells) {
            $pairs = $cells->split(TeamSpeak3::SEPARATOR_CELL);

            //get count of arguments there passed to this function / 0 is similar to !func_num_args() but this variant results in bool(false)
            $tableArgs = func_get_args();
            if ($tableArgs >= 1) {
                for ($i = 0; $i < count($pairs); $i++) {
                    $pairs[$i]->unescape();
                }
            }

            $table[] = $pairs;
        }

        return $table;
    }

    /**
     * Returns a multidimensional array containing the reply split in multiple rows and columns.
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [];
        $table = $this->toTable();

        for ($i = 0; $i < count($table); $i++) {
            foreach ($table[$i] as $pair) {
                if (! count($pair)) {
                    continue;
                }

                if (! $pair->contains(TeamSpeak3::SEPARATOR_PAIR)) {
                    $array[$i][$pair->toString()] = null;
                } else {
                    list($ident, $value) = $pair->split(TeamSpeak3::SEPARATOR_PAIR, 2);

                    //get count of arguments there passed to this function / 0 is similar to !func_num_args() but this variant results in bool(false)
                    //let us make the code more readable to understand what happened. Con is, there is a bit longer
                    $arrayArgs = func_get_args();
                    if ($value->isInt() === true) {
                        $array[$i][$ident->toString()] = $value->toInt();
                    } else {
                        if ($arrayArgs >= 1) {
                            $array[$i][$ident->toString()] = $value->unescape();
                        } else {
                            $array[$i][$ident->toString()] = $value;
                        }
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Returns a multidimensional assoc array containing the reply split in multiple rows and columns.
     * The identifier specified by key will be used while indexing the array.
     *
     * @param  $ident
     * @return array
     * @throws ServerQueryException
     */
    public function toAssocArray($ident): array
    {
        $nodes = (func_num_args() > 1) ? $this->toArray(1) : $this->toArray();
        $array = [];

        foreach ($nodes as $node) {
            if (isset($node[$ident])) {
                $array[(is_object($node[$ident])) ? $node[$ident]->toString() : $node[$ident]] = $node;
            } else {
                throw new ServerQueryException("invalid parameter. ident '$ident' does not exist in node '".json_encode($node)."'", 0x602);
            }
        }

        return $array;
    }

    /**
     * Returns an array containing the reply split in multiple rows and columns.
     *
     * @return array
     */
    public function toList(): array
    {
        //changed $array = func_num_args() ? $this->toArray(1) : $this->toArray();
        //TODO Documentation: not clear what func_num_args() will do it here.
        //TODO func_num_args() results in 0 or greater than 0 but not false so this function result every time in $this->toArray()
        //Reference Host.php line 513 and 971
        $array = $this->toArray();

        if (count($array) == 1) {
            return array_shift($array);
        }

        return $array;
    }

    /**
     * Returns an array containing stdClass objects.
     *
     * @return array
     */
    public function toObjectArray(): array
    {
        $array = (func_num_args() > 1) ? $this->toArray(1) : $this->toArray();

        for ($i = 0; $i < count($array); $i++) {
            $array[$i] = (object) $array[$i];
        }

        return $array;
    }

    /**
     * Returns the command used to get this reply.
     *
     * @return StringHelper
     */
    public function getCommandString(): StringHelper
    {
        return new StringHelper($this->cmd);
    }

    /**
     * Returns an array of events that occured before or during this reply.
     *
     * @return array
     */
    public function getNotifyEvents(): array
    {
        return $this->evt;
    }

    /**
     * Returns the value for a specified error property.
     *
     * @param string $ident
     * @param mixed|null $default
     * @return mixed
     */
    public function getErrorProperty(string $ident, mixed $default = null): mixed
    {
        return (array_key_exists($ident, $this->err)) ? $this->err[$ident] : $default;
    }

    /**
     * Parses a ServerQuery error and throws a PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException object if
     * there's an error.
     *
     * @param StringHelper $err
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     */
    protected function fetchError(StringHelper $err): void
    {
        $cells = $err->section(TeamSpeak3::SEPARATOR_CELL, 1, 3);

        foreach ($cells->split(TeamSpeak3::SEPARATOR_CELL) as $pair) {
            list($ident, $value) = $pair->split(TeamSpeak3::SEPARATOR_PAIR);

            $this->err[$ident->toString()] = $value->isInt() ? $value->toInt() : $value->unescape();
        }

        Signal::getInstance()->emit('notifyError', $this);

        if ($this->getErrorProperty('id', 0x00) != 0x00 && $this->exp) {
            if ($permid = $this->getErrorProperty('failed_permid')) {
                if ($permsid = key($this->con->request('permget permid='.$permid, false)->toAssocArray('permsid'))) {
                    $suffix = ' (failed on '.$permsid.')';
                } else {
                    $suffix = ' (failed on '.$this->cmd->section(TeamSpeak3::SEPARATOR_CELL).' '.$permid.'/0x'.strtoupper(dechex($permid)).')';
                }
            } elseif ($details = $this->getErrorProperty('extra_msg')) {
                $suffix = ' ('.trim($details).')';
            } else {
                $suffix = '';
            }

            throw new ServerQueryException($this->getErrorProperty('msg').$suffix, $this->getErrorProperty('id'), $this->getErrorProperty('return_code'));
        }
    }

    /**
     * Parses a ServerQuery reply and creates a PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper object.
     *
     * @param array $rpl
     * @return void
     * @throws AdapterException
     */
    protected function fetchReply(array $rpl): void
    {
        foreach ($rpl as $key => $val) {
            if ($val->startsWith(TeamSpeak3::TS3_MOTD_PREFIX) || $val->startsWith(TeamSpeak3::TEA_MOTD_PREFIX)) {
                unset($rpl[$key]);
            } elseif ($val->startsWith(TeamSpeak3::EVENT)) {
                $this->evt[] = new Event($val, $this->con);
                unset($rpl[$key]);
            }
        }

        $this->rpl = new StringHelper(implode(TeamSpeak3::SEPARATOR_LIST, $rpl));
    }
}
