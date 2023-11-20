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
    public $toDownload = [];
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
            throw new \Exception("The download list is empty. Please add a Downloadable Object to download list before downloading.");
        }

        $this->downloadPath = $path;

        if (!is_dir($this->downloadPath)) {
            throw new \Exception("The download path '{$this->downloadPath}' is not a directory.");
        }

        foreach ($this->toDownload as $downloadable) {

            if (!$downloadable instanceof Downloadable) {
                throw new \InvalidArgumentException("Invalid object in the download list. All objects must implement the Downloadable interface.");
            }

            $zipContents = $downloadable->download();

            if ($zipContents) {
                $downloadable->save($this->downloadPath);
                $this->downloaded[] = $downloadable;
            } else {
                throw new \RuntimeException("Unable to download zip file {$downloadable->getName()}.");
            }
        }
    }

    public function extract(?string $path = null, ?bool $backup = false): void
    {
        if (empty($this->downloaded)) {
            throw new \Exception("The downloaded list is empty. Please download assets before extracting.");
        }

        $extractPath = $path ?? $this->downloadPath;

        foreach ($this->downloaded as $downloadable) {
            $zipPath = $this->downloadPath . $downloadable->getName();

            if (!file_exists($zipPath)) {
                throw new \RuntimeException("Zip file {$downloadable->getName()} does not exist in the download path.");
            }

            $zip = new \ZipArchive();

            if ($zip->open($zipPath) === true) {
                $zipFolderName = trim($zip->getNameIndex(0), '/');
                $destinationFolderName = $this->repository->getRepositoryName();
                if (is_dir($extractPath . $destinationFolderName)) {
                    // throw new \RuntimeException("Folder {$zipFolderName} already exists in {$extractPath}. Please delete it and try again.");
                    $backupFolderName = $destinationFolderName . ".backup_" . date("Y-m-d_H-i-s");
                    rename($extractPath . $destinationFolderName, $extractPath . $backupFolderName);
                }

                if ($zip->extractTo($extractPath)) {
                    $zip->close();
                    rename($extractPath . $zipFolderName, $extractPath . $destinationFolderName);
                    if (!$backup && isset($backupFolderName)) {
                        $this->deleteDirectory($extractPath . $backupFolderName);
                    }
                } else {
                    if (isset($backupFolderName)) {
                        rename($extractPath . $backupFolderName, $extractPath . $zipFolderName);
                    }
                    throw new \RuntimeException("Unable to extract zip file {$downloadable->getName()} to {$extractPath}. Probably permissions issue.");
                }
            } else {
                throw new \RuntimeException("Unable to locate zip file {$downloadable->getName()}.");
            }
        }
    }

    public function delete(): void {
        foreach ($this->downloaded as $asset) {
            $asset->delete($this->downloadPath);
        }
    }

    public function extractAndDelete(?string $path = null, ?bool $backup = false): void {
        $this->extract($path, $backup);
        $this->delete();
    }

    public function addSourceCodeToDownload(): void
    {
        $this->toDownload[] = $this->release->getSourceCode();
    }

    private function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
    
        if (!is_dir($dir)) {
            return unlink($dir);
        }
    
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
    
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
    
        return rmdir($dir);
    }

}
