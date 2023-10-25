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
use \Ector\ReleaseDownloader\Downloader;

$autoload = dirname(__FILE__) . "/vendor/autoload.php";
if (file_exists($autoload)) require_once $autoload;

$GitHubRepoOwner = "repo_owner_name";
$GitHubRepoName = "repo_name";
$GitHubAccessToken = "github_token";

$downloader = new Downloader($GitHubRepoOwner, $GitHubRepoName, $GitHubAccessToken);
```

### Download the latest release zip file (source code)
```php
$downloader->downloadLatestRelease();
```

### Download the latest asset's zip file of the latest release
```php
$downloader->downloadLatestReleaseAsset();
```

### Get the latest version if installed

Return the version of the module, based on the module name or, if not specified, the repository name. Return null if the module is not installed.

```php
$downloader->getInstalledVersion("some_module_name");
// or 
$downloader->getInstalledVersion(); // Implicit "repo_name"
```

### Get the list of all active Ector modules from Ector Backend
```php
$downloader->getActiveEctorModules();
```