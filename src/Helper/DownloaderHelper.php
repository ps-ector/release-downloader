<?php

namespace Ector\ReleaseDownloader\Helper;

class DownloaderHelper
{
    public static function performCurlRequest($url, $options)
    {
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [$httpcode, $result];
    }

    public function saveFile($name, $content, $path)
    {
        file_put_contents($path . $name, $content);
    }
}
