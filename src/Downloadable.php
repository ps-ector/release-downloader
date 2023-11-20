<?php

namespace Ector\ReleaseDownloader;

interface Downloadable
{
    public function getName(): string;

    public function download(): ?string;

    public function save(string $path): void;

    public function delete(string $path): void;
}
