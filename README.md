# Ector Release Downloader

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ector/release-downloader.svg?style=flat-square)](https://packagist.org/packages/ector/release-downloader)
[![Total Downloads](https://img.shields.io/packagist/dt/ector/release-downloader.svg?style=flat-square)](https://packagist.org/packages/ector/release-downloader)

Composer dependency to download assets from a GitHub repository.

## Installation

You can install the package via composer:

```bash
composer require ector/release-downloader
```

## Usage

```php
<?php 
use \Ector\ReleaseDownloader\Downloader;

$autoload = dirname(__FILE__) . "/vendor/autoload.php";
if (file_exists($autoload)) require_once $autoload;

$GitHubRepoOwner = "repo_owner_name";
$GitHubRepoName = "repo_name";
$GitHubAccessToken = "github_token";
$ReleaseVersion = "0.0.4"; // or null to download the latest version

$downloader = new Downloader($GitHubRepoOwner, $GitHubRepoName, $ReleaseVersion, $GitHubAccessToken);
```

### Get the latest version Tag Name
```php
$downloader->getLatestTagName();
```

### Add the first available asset to download queue
```php
$downloader->addAssetToDownload();
```

### Search and add the asset with name "asset_name.zip" to download queue (if found)
```php
$downloader->addAssetToDownload("asset_name.zip");
```

### Performs the actual download of the assets in the queue
```php
$downloader->download(); // download in the current directory
// or 
$downloader->download("/destination/path/");

```

### Extract downloaded assets
```php
$downloader->extract(); // extract in the same directory where assets are downloaded
// or 
$downloader->extract("/destination/path/");

```
