<?php

namespace Ector\ReleaseDownloader;

use Ector\ReleaseDownloader\Helper\DownloaderHelper;

class Downloader
{
    const GITHUB_API_URL = "https://api.github.com/";
    const GITHUB_API_VERSION = "2022-11-28";
    const USER_AGENT = "EctorReleaseDownloader";
    
    private $repositoryOwner;
    private $repositoryName;
    private $accessToken;
    private $latestRelease;

    public function __construct(string $repositoryOwner, string $repositoryName, ?string $accessToken = null)
    {

        if (!defined('_PS_VERSION_')) {
            throw new \Exception("This class is intended to be used in the PrestaShop context only.");
        }

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
        return $this->latestRelease["tag_name"];
    }

    /**
     * Restituisce la versione del modulo installato basandosi sul nome del repository.
     *
     * @return string|null La versione del modulo se trovato e installato, altrimenti null.
     */
    public function getInstalledVersion(): string|null
    {
        $moduleName = $this->repositoryName;

        foreach ($this->getModules() as $module) {
            if (isset($module['name']) && $module['name'] === $moduleName) {
                return $module['version'];
            }
        }

        return null;
    }

    /**
     * Private Methods
     */

    private function getModules()
    {
        return \Module::getModulesInstalled();
    }

    private function getLatestRelease()
    {
        $url = self::GITHUB_API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/latest";

        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: vnd.github+json',
                'User-Agent: ' . self::USER_AGENT,
                'Authorization: Bearer ' . $this->accessToken,
                'X-GitHub-Api-Version: ' . self::GITHUB_API_VERSION,
            ],
        ];

        $releaseInfo = DownloaderHelper::performCurlRequest($url, $options);

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
        return self::GITHUB_API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/assets/{$assetId}";
    }

    private function downloadFileZip($url)
    {
        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: application/octet-stream',
                'User-Agent: ' . self::USER_AGENT,
                'Authorization: Bearer ' . $this->accessToken,
                'X-GitHub-Api-Version: ' . self::GITHUB_API_VERSION,
            ],
        ];

        return DownloaderHelper::performCurlRequest($url, $options);
    }

    private function downloadFile($url)
    {
        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: vnd.github+json',
                'Authorization: Bearer ' . $this->accessToken,
                'User-Agent: ' . self::USER_AGENT,
                'X-GitHub-Api-Version: ' . self::GITHUB_API_VERSION,
            ],
        ];

        return DownloaderHelper::performCurlRequest($url, $options);
    }

    

    private function saveFile($name, $content)
    {
        file_put_contents(_PS_MODULE_DIR_ . $name, $content);
    }

}
