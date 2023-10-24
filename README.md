# Ector Release Downloader

Composer dependency to check and download the latest version of an Ector module.

## Installation

You can install the package via composer:

```bash
composer require ector/release-downloader
```

## Usage

```php
<?php 

$autoload = dirname(__FILE__) . "/vendor/autoload.php";
if (file_exists($autoload)) require_once $autoload;

use \Ector\ReleaseDownloader\Downloader;

$repoOwner = "repo_owner_name";
$repoName = "repo_name";
$accessToken = "github_token";

$downloader = new Downloader($repoOwner, $repoName, $accessToken);

$downloader->downloadLatestRelease(); // Download the latest release zip file (source code)

$downloader->downloadLatestReleaseAsset(); // Download the latest asset's zip file of the latest release

```

