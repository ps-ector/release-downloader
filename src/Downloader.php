<?php

namespace Ector\ReleaseDownloader;

class Downloader
{
    const GITHUB_API_URL = "https://api.github.com/";
    const GITHUB_API_VERSION = "2022-11-28";
    const USER_AGENT = "EctorReleaseDownloader";
    const BACKEND_API_URL = "http://192.168.1.49:1337/api/";
    const BACKEND_API_TOKEN = "15745c4feb3004cb96eb6f5b86327e809453ae8249a9a2c6502a5e5547a252e79048c8ea6ec337e4789a7cc9ab3ccae70872b4303930edcc694aff8db59ee1e15997c642047c06a2bf0d2ec412538cdc840706ccc7807037b95296da6379b0dafb2799e435a51c4c109bb01a20732f500d708d7f59630704a25d98cdf2458c86";

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

    public function getActiveEctorModules(): array
    {
        $ectorModules = [];
        $activeModules = $this->getActiveEctorModulesApi();
        foreach ($activeModules as $module) {
            $ectorModules[$module['attributes']['name']] = $module['attributes']['version'];
        }
        return $ectorModules;
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

        return $this->performCurlRequest($url, $options);
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

        return $this->performCurlRequest($url, $options);
    }

    private function performCurlRequest($url, $options)
    {
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_VERBOSE, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function saveFile($name, $content)
    {
        file_put_contents(_PS_MODULE_DIR_ . $name, $content);
    }

    private function getActiveEctorModulesApi(): array
    {

        $url = self::BACKEND_API_URL . "modules?filters[active][%24eq]=true";

        $options = [
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . self::BACKEND_API_TOKEN,
            ],
        ];

        $response = json_decode($this->performCurlRequest($url, $options), true);

        return $response['data'];
    }
}
