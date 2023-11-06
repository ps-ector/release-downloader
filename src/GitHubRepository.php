<?php

namespace Ector\ReleaseDownloader;

use Ector\ReleaseDownloader\Helper\DownloaderHelper;

class GitHubRepository
{
    private string $owner;
    private string $name;
    private ?string $accessToken;

    public function __construct(string $owner, string $name, ?string $accessToken = null)
    {
        $this->owner = $owner;
        $this->name = $name;
        $this->accessToken = $accessToken;
    }

    public function getLatestRelease(): Release
    {
        $endpoint = "repos/{$this->owner}/{$this->name}/releases/latest";
        return $this->getRelease($endpoint);
    }

    public function getSpecificRelease(string $version): Release
    {
        $endpoint = "repos/{$this->owner}/{$this->name}/releases/tags/{$version}";
        return $this->getRelease($endpoint);
    }

    public function getReleaseById(int $releaseId): Release
    {
        $endpoint = "repos/{$this->owner}/{$this->name}/releases/{$releaseId}";
        return $this->getRelease($endpoint);
    }

    private function getRelease(string $endpoint): Release
    {
        $url = Downloader::GITHUB_API_URL . $endpoint;

        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: vnd.github+json',
                'User-Agent: ' . Downloader::USER_AGENT,
                'Authorization: Bearer ' . $this->accessToken,
                'X-GitHub-Api-Version: ' . Downloader::GITHUB_API_VERSION,
            ],
        ];

        [$httpCode, $response] = DownloaderHelper::performCurlRequest($url, $options);

        if ($httpCode !== 200) {
            throw new \RuntimeException("No releases found for the specified repository. Please also check the GitHub Token for private repositories.");
        }

        return new Release(json_decode($response, true), $this->accessToken);
    }
}
