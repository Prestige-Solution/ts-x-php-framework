<?php

namespace PlanetTeamSpeak\TeamSpeak3Framework\Adapter;

use PlanetTeamSpeak\TeamSpeak3Framework\Exception\FileTransferException;
use PlanetTeamSpeak\TeamSpeak3Framework\Exception\TransportException;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Profiler;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\Signal;
use PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\TCP;
use PlanetTeamSpeak\TeamSpeak3Framework\Transport\Transport;

/**
 * Class FileTransfer
 * @class FileTransfer
 * @brief Provides low-level methods for file transfer communication with a TeamSpeak 3 Server.
 */
class FileTransfer extends Adapter
{
    /**
     * Connects the PlanetTeamSpeak\TeamSpeak3Framework\Transport\Transport object and performs initial actions on the remote server.
     */
    public function syn(): void
    {
        $this->initTransport($this->options, TCP::class);
        $this->transport->setAdapter($this);

        Profiler::init(spl_object_hash($this));

        Signal::getInstance()->emit('filetransferConnected', $this);
    }

    /**
     * FileTransfer destructor.
     */
    public function __destruct()
    {
        if ($this->getTransport() instanceof Transport && $this->getTransport()->isConnected()) {
            $this->getTransport()->disconnect();
        }
    }

    /**
     * Sends a valid file transfer key to the server to initialize the file transfer.
     *
     * @param string $ftkey
     * @return void
     * @throws FileTransferException
     * @throws TransportException
     */
    protected function init(string $ftkey): void
    {
        if (strlen($ftkey) !== 32 && strlen($ftkey) !== 16) {
            throw new FileTransferException('invalid file transfer key format');
        }

        $this->getProfiler()->start();
        $this->getTransport()->send($ftkey);

        Signal::getInstance()->emit('filetransferHandshake', $this);
    }

    /**
     * Sends the content of a file to the server.
     *
     * @param string $ftkey
     * @param int $seek
     * @param string $data
     * @return void
     * @throws FileTransferException
     * @throws TransportException
     */
    public function upload(string $ftkey, int $seek, string $data): void
    {
        $this->init($ftkey);

        $size = strlen($data);
        $pack = 4096;

        Signal::getInstance()->emit('filetransferUploadStarted', $ftkey, $seek, $size);

        while ($seek < $size) {
            $rest = $size - $seek;
            $chunkSize = min($rest, $pack);
            $buff = substr($data, $seek, $chunkSize);

            $this->getTransport()->send($buff);

            $seek += $chunkSize;

            Signal::getInstance()->emit('filetransferUploadProgress', $ftkey, $seek, $size);
        }

        $this->getProfiler()->stop();

        Signal::getInstance()->emit('filetransferUploadFinished', $ftkey, $seek, $size);
    }

    /**
     * Returns the content of a downloaded file as a PlanetTeamSpeak\TeamSpeak3Framework\Helper\StringHelper object.
     *
     * @param string $ftkey
     * @param int $size
     * @param bool $passthru
     * @return StringHelper|void
     * @throws FileTransferException
     * @throws TransportException
     */
    public function download(string $ftkey, int $size, bool $passthru = false)
    {
        $this->init($ftkey);

        if ($passthru) {
            $this->passthru($size);

            return;
        }

        $buff = new StringHelper('');
        $pack = 4096;

        Signal::getInstance()->emit('filetransferDownloadStarted', $ftkey, count($buff), $size);

        while (count($buff) < $size) {
            $rest = $size - count($buff);
            $chunkSize = min($rest, $pack);
            $data = $this->getTransport()->read($chunkSize);

            if (count($data) === 0) {
                break;
            }

            $buff->append($data->toString());

            Signal::getInstance()->emit('filetransferDownloadProgress', $ftkey, count($buff), $size);
        }

        $this->getProfiler()->stop();

        Signal::getInstance()->emit('filetransferDownloadFinished', $ftkey, count($buff), $size);

        if (count($buff) !== $size) {
            throw new FileTransferException('incomplete file download ('.count($buff).' of '.$size.' bytes)');
        }

        return $buff;
    }

    /**
     * Outputs all remaining data on a TeamSpeak 3 file transfer stream using PHP's fpassthru()
     * function.
     *
     * @param int $size
     * @return void
     * @throws FileTransferException
     */
    protected function passthru(int $size): void
    {
        $stream = $this->getTransport()->getStream();

        if (! is_resource($stream)) {
            throw new FileTransferException('invalid file transfer stream');
        }

        $buffSize = fpassthru($stream);

        if ($buffSize !== $size) {
            throw new FileTransferException('incomplete file download ('.$buffSize.' of '.$size.' bytes)');
        }
    }
}
