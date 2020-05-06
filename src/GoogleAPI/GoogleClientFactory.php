<?php


namespace TheCodingMachine\GooglePowerpoint\GoogleAPI;

use Google_Client;
use Google_Service_Drive;
use Google_Service_Slides;

class GoogleClientFactory
{
    /** @var Google_Client */
    private $client;
    /** @var Google_Service_Slides */
    private $slideService;
    /** @var Google_Service_Drive */
    private $driveService;

    public function __construct()
    {
        $client = new Google_Client();
        $client->useApplicationDefaultCredentials();
        $client->setApplicationName('Google Slides API client');
        $client->setScopes([Google_Service_Slides::PRESENTATIONS, Google_Service_Drive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setPrompt('none');

        $this->client = $client;
        
        $this->slideService = new Google_Service_Slides($this->client);
        $this->driveService = new Google_Service_Drive($this->client);
    }
    
    public function getSlideService(): Google_Service_Slides
    {
        return $this->slideService;
    }

    public function getDriveService(): Google_Service_Drive
    {
        return $this->driveService;
    }
}
