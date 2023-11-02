<?php 
namespace Ector\ReleaseDownloader;

class Release
{
    // private array $data;
    private array $assets = [];

    public function __construct(array $data)
    {
        // $this->data = $data;

        if (!empty($data['assets']) && is_array($data['assets'])) {
            foreach ($data['assets'] as $asset) {
                $this->assets[] = new ReleaseAsset($asset);
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
}