<?php 
namespace Ector\ReleaseDownloader;

class Release
{
    // @var array $data
    private $data = [];
    // @var array $assets
    private $assets = [];
    // @var null|string $accessToken
    private $accessToken;

    public function __construct(array $data, ?string $accessToken=null)
    {
        $this->accessToken = $accessToken;
        $this->data = $data;

        if (!empty($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                $this->assets[] = new ReleaseAsset($asset, $this->accessToken);
            }
        }
    }

    public function getAssetByName(string $name): ?ReleaseAsset
    {
        foreach ($this->assets as $asset) {
            if ($asset->getName() === $name) {
                return $asset;
            }
        }
        return null;
    }

    public function getDefaultAsset(): ?ReleaseAsset
    {
        return $this->assets[0];
    }

    public function getAssets(): array
    {
        return $this->assets;
    }

    public function getTagName(): string
    {
        return $this->data['tag_name'];
    }
}
