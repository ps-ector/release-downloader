<?php

namespace Ector\ReleaseDownloader;

class Downloader {
    private $repositoryOwner;
    private $repositoryName;
    private $accessToken;
    private $commonHeaders = [];
    
    public function __construct($repositoryOwner, $repositoryName, $accessToken) {
        $this->repositoryOwner = $repositoryOwner;
        $this->repositoryName = $repositoryName;
        $this->accessToken = $accessToken;

        $this->commonHeaders = [
            "Accept: application/vnd.github+json",
            "User-Agent: EctorReleaseDownloader",
            "Authorization: Bearer {$this->accessToken}"
        ];
    }
    
    public function downloadLatestRelease() {
        $latestRelease = $this->getLatestRelease();

        if ($latestRelease) {
            $zipUrl = $latestRelease['zipball_url'];
            $zipContents = $this->downloadFile($zipUrl);

            if ($zipContents) {
                file_put_contents("latest_release.zip", $zipContents);
                echo "Zip file downloaded successfully.";
            } else {
                echo "Unable to download zip file.";
            }
        } else {
            echo "No releases found for the specified repository.";
        }
    }
    
    private function getLatestRelease() {
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
            return null;
        }
    }

    private function downloadFile($url) {
        $options = [
            'http' => [
                'header' => implode("\r\n", $this->commonHeaders),
            ],
        ];

        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

}

// Esempio di utilizzo
$repoOwner = "buggyzapa";
$repoName = "ector";
$accessToken = "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";

$downloader = new Downloader($repoOwner, $repoName, $accessToken);
$downloader->downloadLatestRelease();

?>
