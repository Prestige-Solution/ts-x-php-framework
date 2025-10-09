<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\AdapterException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
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
     * Sotres an array of events that occurred before or during this reply.
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

    protected array $error = [];

    /**
     * Creates a new PlanetTeamSpeak\TeamSpeak3Framework\Adapter\ServerQuery\Reply object.
     *
     * @param  array  $rpl
     * @param  string  $cmd
     * @param  Host|null  $con
     * @param  bool  $exp
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
     */
    public function __construct(array $rpl, string $cmd = '', Host $con = null, bool $exp = true)
    {
        $this->cmd = new StringHelper($cmd);
        $this->con = $con;
        $this->exp = $exp;

        $err = array_pop($rpl);

        // Validate error object
        if ($err instanceof StringHelper) {
            $this->fetchError($err);
        } else {
            // Fallback: no error returned → initialize as empty
            $this->error = [
                'id'  => 0,
                'msg' => 'ok',
            ];
        }

        $this->fetchReply($rpl);
    }

    /**
     * Returns the reply as a PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper object.
     *
     * @return StringHelper|null
     */
    public function toString(): null|StringHelper
    {
        if (func_get_args() > 0) {
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

        $linesRaw = explode(TeamSpeak3::SEPARATOR_LIST, $this->toString());

        return array_map(fn ($line) => new NodeValue($line), $linesRaw);
    }

    /**
     * Returns the reply as a standard PHP array where each element represents one item in table format.
     */
    public function toTable(): array
    {
        $table = [];

        foreach ($this->toLines() as $line) {
            $cells = $line->split(TeamSpeak3::SEPARATOR_CELL);
            $table[] = $cells;
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

        foreach ($this->toTable() as $i => $row) {
            foreach ($row as $pair) {
                if (! $pair->toString()) {
                    continue;
                }

                if (! $pair->contains(TeamSpeak3::SEPARATOR_PAIR)) {
                    $array[$i][$pair->toString()] = null;
                } else {
                    list($ident, $value) = $pair->split(TeamSpeak3::SEPARATOR_PAIR, 2);

                    if (is_numeric($value->toString())) {
                        $array[$i][$ident->toString()] = $value->toInt();
                    } else {
                        $array[$i][$ident->toString()] = $value->unescape();
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Returns a multidimensional assoc array containing the reply split in multiple rows and columns.
     * The identifier specified by a key will be used while indexing the array.
     */
    public function toAssocArray($ident): array
    {
        $nodes = $this->toArray();
        $assoc = [];

        foreach ($nodes as $node) {
            if (! isset($node[$ident])) {
                continue;
            }

            $key = $node[$ident];
            $assoc[$key] = $node;
        }

        return $assoc;
    }

    /**
     * Returns an array containing the reply split in multiple rows and columns.
     *
     * @return array
     */
    public function toList(): array
    {
        $array = $this->toArray();

        if (count($array) === 1) {
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
     * Returns an array of events that occurred before or during this reply.
     *
     * @return array
     */
    public function getNotifyEvents(): array
    {
        return $this->evt;
    }

    public function getErrorProperty(string $ident, mixed $default = null): mixed
    {
        if (array_key_exists($ident, $this->err)) {
            return $this->err[$ident];
        }

        if (array_key_exists($ident, $this->error)) {
            return $this->error[$ident];
        }

        return $default ?? new StringHelper('');
    }

    /**
     * Parses a ServerQuery error and throws a PlanetTeamSpeak\TeamSpeak3Framework\Exception\ServerQueryException object if
     * there's an error.
     *
     * @param  StringHelper  $err
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
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
     * @param  array  $rpl
     * @return void
     * @throws AdapterException
     * @throws ServerQueryException
     * @throws TransportException
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
