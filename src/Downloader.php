<?php

namespace Ector\ReleaseDownloader;

class Downloader
{
    private $repositoryOwner;
    private $repositoryName;
    private $accessToken;
    private $latestRelease;
    private $commonHeaders = [
        "Accept: application/vnd.github+json",
        "User-Agent: EctorReleaseDownloader",
    ];

    public function __construct($repositoryOwner, $repositoryName, $accessToken = null)
    {
        if (!$repositoryOwner || !$repositoryName) {
            throw new \Exception("Missing repository owner or repository name.");
        }

        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName = $repositoryName;
        $this->accessToken = $accessToken;

        if ($this->accessToken !== null) {
            $this->commonHeaders[] = "Authorization: Bearer {$this->accessToken}";
        }

        $this->latestRelease = $this->getLatestRelease();
    }

    private function getLatestRelease()
    {
        $url = "https://api.github.com/repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/latest";

        $options = [
            'http' => [
                'header' => implode("\r\n", $this->commonHeaders),
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

    private function getLatestReleaseVersion()
    {
        return $this->latestRelease["tag_name"];
    }

    private function getLatestReleaseAssets()
    {
        return $this->latestRelease["assets"][0];
    }

    private function getLatestReleaseAssetsName()
    {
        return $this->getLatestReleaseAssets()["name"];
    }

    private function getLatestReleaseAssetsDownloadUrl()
    {
        return $this->getLatestReleaseAssets()["browser_download_url"];
    }

    private function downloadFile($url)
    {
        $options = [
            'http' => [
                'header' => implode("\r\n", $this->commonHeaders),
            ],
        ];
        
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

    private function saveFile($name, $content) {
        file_put_contents($name . ".zip", $content);
    }

    // TODO: downloadLatestRelease() funziona, downloadLatestReleaseAssets() no...
    public function downloadLatestReleaseAssets()
    {
        $zipContents = $this->downloadFile($this->getLatestReleaseAssetsDownloadUrl());

        if ($zipContents) {
            $this->saveFile($this->getLatestReleaseAssetsName() . ".zip", $zipContents);
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
            $this->saveFile("latest_release", $zipContents);
            echo "Zip file downloaded successfully.";
        } else {
            throw new \Exception("Unable to download zip file.");
        }
    }

}
