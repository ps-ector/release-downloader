<?php

namespace Ector\ReleaseDownloader;

class Downloader
{
    const GITHUB_API_URL = "https://api.github.com/";
    const GITHUB_API_VERSION = "2022-11-28";
    const USER_AGENT = "EctorReleaseDownloader";

    private GitHubRepository $repository;
    private Release $release;
    private array $toDownload = [];
    private string $downloadPath = "./";
    private array $downloaded = [];

    public function __construct(string $repositoryOwner, string $repositoryName, ?string $version = null, ?string $accessToken = null)
    {
        $this->repository = new GitHubRepository($repositoryOwner, $repositoryName, $accessToken);

        if (!empty($version)) {
            $this->release = $this->repository->getSpecificRelease($version);
        } else {
            $this->release = $this->repository->getLatestRelease();
        }
    }

    public function getAssets(): array
    {
        return $this->release->getAssets();
    }

    public function addAssetToDownload(?string $assetName = null): void
    {
        if ($this->release) {
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
        } else {
            throw new \Exception("No release available. Please specify a release before adding assets to download.");
        }
    }

    public function download(?string $path = "./"): void
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

        $extactPath = $path ?? $this->downloadPath;

        foreach ($this->downloaded as $asset) {
            $zipPath = $this->downloadPath . $asset->getName();

            if (!file_exists($zipPath)) {
                throw new \RuntimeException("Zip file {$asset->getName()} does not exist in the download path.");
            }

            $zip = new \ZipArchive();

            if ($zip->open($zipPath) === true) {
                $zip->extractTo("../".dirname($extactPath));
                $zip->close();
            } else {
                throw new \RuntimeException("Unable to extract zip file {$asset->getName()}.");
            }
        }
    }
}
