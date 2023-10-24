<?php

namespace Ector\ReleaseDownloader;

class Downloader
{
    const API_URL = "https://api.github.com/";
    const API_VERSION = "2022-11-28";
    const USER_AGENT = "EctorReleaseDownloader";

    private $repositoryOwner;
    private $repositoryName;
    private $accessToken;
    private $latestRelease;

    public function __construct($repositoryOwner, $repositoryName, $accessToken = null)
    {
        // TODO: CHECK: This class is intended to be used on an PrestaShop instance only

        if (!$repositoryOwner || !$repositoryName) {
            throw new \Exception("Missing repository owner or repository name.");
        }

        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName = $repositoryName;
        $this->accessToken = $accessToken;

        $this->latestRelease = $this->getLatestRelease();
    }

    /**
     * Public Methods
     */
    public function downloadLatestReleaseAsset()
    {
        $zipUrl = $this->getLatestReleaseAssetDownloadUrl();
        $zipContents = $this->downloadFileZip($zipUrl);

        if ($zipContents) {
            $this->saveFile($this->getLatestReleaseAssetName(), $zipContents);
            return true;
        } else {
            throw new \Exception("Unable to download zip file.");
        }
    }

    public function downloadLatestRelease()
    {
        $zipUrl = $this->latestRelease['zipball_url'];
        $zipContents = $this->downloadFile($zipUrl);

        if ($zipContents) {
            $this->saveFile("latest_release-{$this->getLatestReleaseVersion()}.zip", $zipContents);
        } else {
            throw new \Exception("Unable to download zip file.");
        }
    }

    public function getLatestReleaseVersion()
    {
        return $this->latestRelease["tag_name"];
    }

    public function getInstalledVersion() {

    }

    /**
     * Private Methods
     */
    private function getLatestRelease()
    {
        $url = self::API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/latest";

        $options = [
            'http' => [
                'header' => implode("\r\n", [
                    'Accept: vnd.github+json',
                    'User-Agent: ' . self::USER_AGENT,
                    'Authorization: Bearer ' . $this->accessToken,
                    'X-GitHub-Api-Version: ' . self::API_VERSION,
                ]),
            ],
        ];

        $context = stream_context_create($options);

        $releaseInfo = @file_get_contents($url, false, $context);

        if ($releaseInfo) {
            return json_decode($releaseInfo, true);
        } else {
            throw new \Exception("No releases found for the specified repository. Please also check the GitHub Token for private repositories.");
        }
    }

    private function getLatestReleaseAsset()
    {
        return $this->latestRelease["assets"][0];
    }

    private function getLatestReleaseAssetId()
    {
        return $this->getLatestReleaseAsset()["id"];
    }

    private function getLatestReleaseAssetName()
    {
        return $this->getLatestReleaseAsset()["name"];
    }

    private function getLatestReleaseAssetDownloadUrl()
    {
        // return $this->getLatestReleaseAsset()["browser_download_url"];
        $assetId = $this->getLatestReleaseAssetId();
        return self::API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/assets/{$assetId}";
    }

    private function downloadFileZip($url)
    {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/octet-stream',
            'User-Agent: ' . self::USER_AGENT,
            'Authorization: Bearer ' . $this->accessToken,
            'X-GitHub-Api-Version: ' . self::API_VERSION,
        ]);
        $zipFileContents = curl_exec($ch);
        curl_close($ch);
        return $zipFileContents;
    }

    private function downloadFile($url)
    {
        $options = [
            'http' => [
                'header' => implode("\r\n", [
                    'Accept: vnd.github+json',
                    'Authorization: Bearer ' . $this->accessToken,
                    'User-Agent: ' . self::USER_AGENT,
                    'X-GitHub-Api-Version: ' . self::API_VERSION,
                ]),
            ],
        ];

        $context = stream_context_create($options);
        $content = file_get_contents($url, false, $context);
        return $content;
    }

    private function saveFile($name, $content)
    {
        file_put_contents($name, $content);
    }
}
