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

    public function __construct(string $repositoryOwner, string $repositoryName, ?string $accessToken = null)
    {
        // TODO: CHECK: This class is intended to be used on an PrestaShop instance only

        if (empty($repositoryOwner) || empty($repositoryName)) {
            throw new \InvalidArgumentException("Missing repository owner or repository name.");
        }

        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName = $repositoryName;
        $this->accessToken = $accessToken;

        $this->latestRelease = $this->getLatestRelease();
    }

    /**
     * Public Methods
     */
    public function downloadLatestReleaseAsset(): void
    {
        $zipUrl = $this->getLatestReleaseAssetDownloadUrl();
        $zipContents = $this->downloadFileZip($zipUrl);

        if ($zipContents) {
            $this->saveFile($this->getLatestReleaseAssetName(), $zipContents);
        } else {
            throw new \RuntimeException("Unable to download zip file.");
        }
    }

    public function downloadLatestRelease(): void
    {
        $zipUrl = $this->latestRelease['zipball_url'];
        $zipContents = $this->downloadFile($zipUrl);

        if ($zipContents) {
            $this->saveFile("latest_release-{$this->getLatestReleaseVersion()}.zip", $zipContents);
        } else {
            throw new \RuntimeException("Unable to download zip file.");
        }
    }

    public function getLatestReleaseVersion(): string
    {
        return "v".$this->latestRelease["tag_name"];
    }

    /**
     * Private Methods
     */

    private function getLatestRelease()
    {
        $url = self::API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/latest";

        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: vnd.github+json',
                'User-Agent: ' . self::USER_AGENT,
                'Authorization: Bearer ' . $this->accessToken,
                'X-GitHub-Api-Version: ' . self::API_VERSION,
            ],
        ];

        $releaseInfo = $this->performCurlRequest($url, $options);

        if ($releaseInfo) {
            return json_decode($releaseInfo, true);
        } else {
            throw new \RuntimeException("No releases found for the specified repository. Please also check the GitHub Token for private repositories.");
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
        $assetId = $this->getLatestReleaseAssetId();
        return self::API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/assets/{$assetId}";
    }

    private function downloadFileZip($url)
    {
        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: application/octet-stream',
                'User-Agent: ' . self::USER_AGENT,
                'Authorization: Bearer ' . $this->accessToken,
                'X-GitHub-Api-Version: ' . self::API_VERSION,
            ],
        ];

        return $this->performCurlRequest($url, $options);
    }

    private function downloadFile($url)
    {
        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: vnd.github+json',
                'Authorization: Bearer ' . $this->accessToken,
                'User-Agent: ' . self::USER_AGENT,
                'X-GitHub-Api-Version: ' . self::API_VERSION,
            ],
        ];

        return $this->performCurlRequest($url, $options);
    }

    private function performCurlRequest($url, $options)
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

    private function saveFile($name, $content)
    {
        file_put_contents($name, $content);
    }
}
