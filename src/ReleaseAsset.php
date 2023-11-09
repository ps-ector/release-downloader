<?php

namespace Ector\ReleaseDownloader;

use Ector\ReleaseDownloader\Helper\DownloaderHelper;

class ReleaseAsset
{
    // @var string $name
    private $name;
    // @var string $downloadUrl
    private $downloadUrl;
    // @var null|string $accessToken
    private $accessToken;

    public function __construct(array $data, ?string $accessToken = null)
    {
        $this->accessToken = $accessToken;
        $this->name = $data['name'];
        $this->downloadUrl = $data['url'];
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

    public function delete(string $path): void
    {
        unlink($path . $this->name);
    }
}
