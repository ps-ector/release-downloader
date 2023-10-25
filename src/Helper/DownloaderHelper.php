<?php

namespace Ector\ReleaseDownloader\Helper;

class DownloaderHelper
{

    const BACKEND_API_URL = "http://192.168.1.49:1337/api/";
    const BACKEND_API_TOKEN = "15745c4feb3004cb96eb6f5b86327e809453ae8249a9a2c6502a5e5547a252e79048c8ea6ec337e4789a7cc9ab3ccae70872b4303930edcc694aff8db59ee1e15997c642047c06a2bf0d2ec412538cdc840706ccc7807037b95296da6379b0dafb2799e435a51c4c109bb01a20732f500d708d7f59630704a25d98cdf2458c86";


    public static function performCurlRequest($url, $options)
    {
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public static function getActiveEctorModules(): array
    {

        $url = self::BACKEND_API_URL . "modules?filters[active][%24eq]=true";

        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . self::BACKEND_API_TOKEN,
            ],
        ];

        $response = json_decode(self::performCurlRequest($url, $options), true);

        $ectorModules = [];

        foreach ($response['data'] as $module) {
            $ectorModules[$module['attributes']['name']] = $module['attributes']['version'];
        }

        return $ectorModules;
    }
}
