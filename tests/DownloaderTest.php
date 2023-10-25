<?php 

use PHPUnit\Framework\TestCase;
use Ector\ReleaseDownloader\Downloader;

class DownloaderTest extends TestCase
{
    public function testGetLatestReleaseVersion()
    {
        // Inizializza l'oggetto Downloader con i dati di prova
        $downloader = new Downloader('owner', 'repository');

        // Esegui il test
        $version = $downloader->getLatestReleaseVersion();

        // Assicurati che il risultato sia corretto
        $this->assertEquals('1.0.0', $version); // Sostituisci con il valore corretto
    }

    // Aggiungi altri test simili per altre funzionalità della tua classe Downloader
}
