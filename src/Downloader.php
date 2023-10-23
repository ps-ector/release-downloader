<?php

namespace Ector\ReleaseDownloader;

class Downloader
{
    const API_URL = "https://api.github.com/";

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
        $url = self::API_URL . "repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/latest";

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
        // $assetId = $this->getLatestReleaseAssetsId();
        // return self::API_URL."repos/{$this->repositoryOwner}/{$this->repositoryName}/releases/assets/{$assetId}";
    }

    private function downloadFileZip($url)
    {
        // $headers = [
        //     "Accept: application/octet-stream",
        //     "User-Agent: EctorReleaseDownloader",
        //     "Authorization: Bearer {$this->accessToken}",
        // ];

        // $options = [
        //     'http' => [
        //         'header' => implode("\r\n", $headers),
        //     ],
        // ];

        // $context = stream_context_create($options);
        // return file_get_contents($url, false, $context);




        // Inizializza una risorsa cURL
        $ch = curl_init();

        // Imposta l'URL del file ZIP da scaricare
        curl_setopt($ch, CURLOPT_URL, $url);

        // Imposta l'opzione CURLOPT_RETURNTRANSFER per ottenere il contenuto del file
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Aggiungi user agent
        $userAgent = 'EctorReleaseDownloader'; // Sostituisci con il tuo User-Agent desiderato
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/octet-stream'));


        // Aggiungi un'intestazione Authorization
        $authorizationHeader = 'Bearer '.$this->accessToken; // Sostituisci con il token di autorizzazione desiderato
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . $authorizationHeader));
    

        // Esegue la richiesta cURL
        $zipFileContents = curl_exec($ch);

        // Verifica se c'è stato un errore durante la richiesta cURL
        if (curl_errno($ch)) {
            echo 'Errore durante il download del file ZIP: ' . curl_error($ch);
            exit;
        }

        // Chiude la risorsa cURL
        curl_close($ch);
        
        return $zipFileContents;
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

    private function saveFile($name, $content)
    {
        file_put_contents($name, $content);
    }

    public function downloadLatestReleaseAssets()
    {
        $zipContents = $this->downloadFileZip($this->getLatestReleaseAssetsDownloadUrl());

        if ($zipContents) {
            var_dump($zipContents);
            $this->saveFile($this->getLatestReleaseAssetsName(), $zipContents);
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
            $this->saveFile("latest_release.zip", $zipContents);
            echo "Zip file downloaded successfully.";
        } else {
            throw new \Exception("Unable to download zip file.");
        }
    }
}

// Esempio di utilizzo
$repoOwner = "buggyzap";
$repoName = "ector";
$accessToken = "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";

$downloader = new Downloader($repoOwner, $repoName, $accessToken);
$downloader->downloadLatestReleaseAssets();
