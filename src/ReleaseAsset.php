<?php

namespace Ector\ReleaseDownloader;

use Ector\ReleaseDownloader\Helper\DownloaderHelper;

class ReleaseAsset
{
    private string $name;
    private string $downloadUrl;
    private ?string $accessToken;

    public function __construct(array $data, ?string $accessToken = null)
    {
        $this->accessToken = $accessToken;
        $this->name = $data['name'];
        $this->downloadUrl = $data['browser_download_url'];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function download(): ?string
    {
        $headers = [
            'Accept: application/octet-stream',
            'User-Agent: ' . Downloader::USER_AGENT,
            'X-GitHub-Api-Version: ' . Downloader::GITHUB_API_VERSION,
        ];

        if ($this->accessToken !== null) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $options = [
            CURLOPT_HTTPHEADER => $headers
        ];

        [$httpCode, $response] = DownloaderHelper::performCurlRequest($this->downloadUrl, $options);

        if ($httpCode !== 200) {
            return null;
        }

        return $response;
    }

    public function save(string $path): void
    {
        file_put_contents($path . $this->name, $this->download());
    }
}
