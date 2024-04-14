<?php

namespace App;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class Twitch
{
    private const OUTPUT_DIR = '/media/twitch/';

    private const BASE_URL = 'https://twitch.tv/';

    public function __construct()
    {
        if (!file_exists(self::OUTPUT_DIR)) {
            mkdir(self::OUTPUT_DIR);
        }
        if (!is_dir(self::OUTPUT_DIR)) {
            throw new HttpException(500, 'Output directory does not exist: '.self::OUTPUT_DIR);
        }
    }

    public function download(string $channelName = null): Response
    {
        $channelName = trim($channelName ?? '');
        if (!$channelName) {
            throw new NotFoundHttpException('No or empty channel name received');
        }
        if (preg_match('/[\s\/\\\\]/', $channelName)) {
            throw new NotFoundHttpException('Channel name contains invalid characters');
        }

        if ($this->isRunning($channelName)) {
            throw new ConflictHttpException($channelName.': The channel is already running');
        }

        $cmd = 'yt-dlp -f b --no-part --hls-use-mpegts -P '.self::OUTPUT_DIR.' '.self::BASE_URL.$channelName.' 2>&1';
        $fp = popen($cmd, 'r');

        $output = '';
        while ($row = fgets($fp)) {
            if (preg_match('/\[info\].*(Downloading \d format.*)/', $row, $matches)) {
                $output .= $matches[1].\PHP_EOL;
            }
            if (preg_match('/\[download\] (Destination: .*)/', $row, $matches)) {
                $output .= $matches[1].\PHP_EOL;

                return new StreamedResponse(function () use ($fp, $output) {
                    echo $output;
                    fpassthru($fp);
                    echo pclose($fp);
                });
            }

            if (!preg_match('/^\[(twitch:stream|info)\]/', $row)) {
                pclose($fp);

                if ('ERROR:' === substr($row, 0, 6)) {
                    $row = trim(substr($row, 6));
                    if (str_contains($row, 'is not currently live')) {
                        throw new ServiceUnavailableHttpException(null, $row);
                    }

                    throw new HttpException(Response::HTTP_BAD_GATEWAY, $row);
                }

                throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Unknown row received: '.$row);
            }
        }

        throw new HttpException(Response::HTTP_BAD_GATEWAY, 'Unexpected end of process: '.pclose($fp));
    }

    public function list(): Response
    {
        return new Response(implode("\n", $this->getRunning()));
    }

    protected function isRunning(string $channelName): bool
    {
        return \in_array($channelName, $this->getRunning(), true);
    }

    protected function getRunning(): array
    {
        $cmdlineFiles = glob('/proc/*/cmdline');
        $channels = [];
        foreach ($cmdlineFiles as $cmdlineFile) {
            $fp = fopen($cmdlineFile, 'r');
            $cmdline = fread($fp, 8192);
            $parts = explode("\0", $cmdline);

            $foundYtDlp = false;
            foreach ($parts as $part) {
                if ('/usr/bin/yt-dlp' === $part) {
                    $foundYtDlp = true;
                }
                if ($foundYtDlp && self::BASE_URL === substr($part, 0, \strlen(self::BASE_URL))) {
                    $channels[] = substr($part, \strlen(self::BASE_URL));
                }
            }
            fclose($fp);
        }

        return $channels;
    }
}
