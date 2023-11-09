<?php

namespace Ector\ReleaseDownloader;

class Downloader
{
    const GITHUB_API_URL = "https://api.github.com/";
    const GITHUB_API_VERSION = "2022-11-28";
    const USER_AGENT = "EctorReleaseDownloader";

    // @var \Ector\ReleaseDownloader\GitHubRepository
    private $repository;
    // @var \Ector\ReleaseDownloader\Release
    private $release;
    // @var array
    private $toDownload = [];
    // @var string
    private $downloadPath = "./";
    // @var array
    private $downloaded = [];

    public function __construct(string $repositoryOwner, string $repositoryName, ?string $version = null, ?string $accessToken = null)
    {
        $this->repository = new GitHubRepository($repositoryOwner, $repositoryName, $accessToken);

        if (!empty($version)) {
            $this->release = $this->repository->getSpecificRelease($version);
        } else {
            $this->release = $this->repository->getLatestRelease();
        }
    }

    public function getRelease() {
        return $this->release;
    }

    public function getLatestTagName() : string
    {
        $latestRelease = $this->repository->getLatestRelease();
        return $latestRelease->getTagName();
    }

    public function getAssets(): array
    {
        return $this->release->getAssets();
    }

    public function addAssetToDownload(?string $assetName = null): void
    {
        if ($assetName) {
            $asset = $this->release->getAssetByName($assetName);
        } else {
            $asset = $this->release->getDefaultAsset();
        }

        if ($asset) {
            $this->toDownload[] = $asset;
        } else {
            throw new \Exception("No asset available.");
        }
    }

    public function download(string $path): void
    {
        if (empty($this->toDownload)) {
            throw new \Exception("The download list is empty. Please add assets to download list before downloading.");
        }

        $this->downloadPath = $path;

        foreach ($this->toDownload as $asset) {
            $zipContents = $asset->download();

            if ($zipContents) {
                $asset->save($this->downloadPath);
                $this->downloaded[] = $asset;
            } else {
                throw new \RuntimeException("Unable to download zip file {$asset->getName()}.");
            }
        }
    }

    public function extract(?string $path = null): void
    {
        if (empty($this->downloaded)) {
            throw new \Exception("The downloaded list is empty. Please download assets before extracting.");
        }

        $extractPath = $path ?? $this->downloadPath;
        // $extractPath = dirname($extractPath);

        foreach ($this->downloaded as $asset) {
            $zipPath = $this->downloadPath . $asset->getName();

            if (!file_exists($zipPath)) {
                throw new \RuntimeException("Zip file {$asset->getName()} does not exist in the download path.");
            }

            $zip = new \ZipArchive();

            if ($zip->open($zipPath) === true) {
                if ($zip->extractTo($extractPath)) {
                    $zip->close();
                } else {
                    throw new \RuntimeException("Unable to extract zip file {$asset->getName()} to {$extractPath}. Probably permissions issue.");
                }
            } else {
                throw new \RuntimeException("Unable to locate zip file {$asset->getName()}.");
            }
        }
    }

    public function remove(): void {
        foreach ($this->downloaded as $asset) {
            $asset->delete($this->downloadPath);
        }
    }

    public function extractAndRemove(?string $path = null): void {
        $this->extract($path);
        $this->remove();
    }
}
