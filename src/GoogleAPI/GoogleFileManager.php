<?php


namespace TheCodingMachine\GooglePowerpoint\GoogleAPI;

use Google_Service_Drive_DriveFile;
use Google_Service_Slides_BatchUpdatePresentationRequest;
use Google_Service_Slides_Page;
use Google_Service_Slides_PageElement;
use GuzzleHttp\Psr7\Response;
use TheCodingMachine\GooglePowerpoint\Exceptions\GoogleApiException;

class GoogleFileManager
{
    const GOOGLE_DRIVE_PRESENTATION_FORMAT = 'application/vnd.google-apps.presentation';
    const MICROSOFT_PRESENTATION_FORMAT = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';

    /** @var \Google_Service_Drive */
    private $googleDriveService;
    /** @var \Google_Service_Slides */
    private $googleSlideService;

    public function __construct(GoogleClientFactory $googleClientFactory)
    {
        $this->googleDriveService = $googleClientFactory->getDriveService();
        $this->googleSlideService = $googleClientFactory->getSlideService();
    }

    public function uploadTemplateFile(string $name, string $content): void
    {
        try {
            //delete old file
            $id = $this->getByName($name)->getId();
            $this->googleDriveService->files->delete($id);
        } catch (\Exception $e) {
            //do nothing if no old file is found
        }
        //upload template file to ggogle drive and get its id.
        $fileMetadata = new Google_Service_Drive_DriveFile(['name' => $name, 'mimeType' => self::GOOGLE_DRIVE_PRESENTATION_FORMAT]);
        //$fileMetadata->setParents(['1gxG5S7nO34EnH20tCxPgihi4HBcgC9Dh']); //todo remove this line in the final version
        $this->googleDriveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => self::MICROSOFT_PRESENTATION_FORMAT,
            'uploadType' => 'multipart',
            'fields' => 'id']);
    }

    public function copyTemplate(string $templateFileName): GoogleSlideTemporaryFile
    {
        $template = $this->getByName($templateFileName);
        $templateId = $template->getId();
        $copy = new Google_Service_Drive_DriveFile([
            'name' => 'editedPresentation' //todo: use a variable name
        ]);
        $id = $this->googleDriveService->files->copy($templateId, $copy, ['fields' => 'id'])->getId();
        return new GoogleSlideTemporaryFile($id);
    }

    /**
     * @param mixed[] $requests
     */
    public function editTempFile(GoogleSlideTemporaryFile $slideTemporaryFile, array $requests): void
    {
        $batchUpdateRequest = new Google_Service_Slides_BatchUpdatePresentationRequest(['requests' => $requests]);
        $this->googleSlideService->presentations->batchUpdate($slideTemporaryFile->getId(), $batchUpdateRequest);
    }

    public function downloadTempFileData(GoogleSlideTemporaryFile $presentation): string
    {
        /** @var Response $response */
        $response = $this->googleDriveService->files->export($presentation->getId(), self::MICROSOFT_PRESENTATION_FORMAT);
        return (string) $response->getBody();
    }
    
    public function getTableObjectId(GoogleSlideTemporaryFile $slideTemporaryFile, string $identifier): string
    {
        $res = $this->googleSlideService->presentations->get($slideTemporaryFile->getId(), ['fields' => 'slides(objectId)']);
        /** @var Google_Service_Slides_Page $slide */
        foreach ($res->getSlides() as $slide) {
            $res2 = $this->googleSlideService->presentations_pages->get($slideTemporaryFile->getId(), $slide->getObjectId(), ['fields' => 'pageElements(objectId,title,table(rows))']);
            /** @var Google_Service_Slides_PageElement $element */
            foreach ($res2->getPageElements() as $element) {
                /** @var bool $isTable */
                $isTable = $element->getTable();
                if ($isTable && $identifier === $element->getTitle()) {
                    return $element->getObjectId();
                }
            }
        }
        throw new GoogleApiException("Could not find an table for title $identifier");
    }


    public function deleteTemplateFile(GoogleTemplateFileInterface $templateFile): void
    {
        $this->googleDriveService->files->delete($templateFile->getId());
    }

    public function deleteTempFile(GoogleSlideTemporaryFile $presentation): void
    {
        $this->googleDriveService->files->delete($presentation->getId());
    }
    
    public function getByName(string $name): Google_Service_Drive_DriveFile
    {
        $filesList = $this->googleDriveService->files->listFiles(['q' => "name = '$name'", 'fields' => 'files(id)'])->getFiles();
        if (count($filesList) === 0) {
            throw new GoogleApiException('No template file found for name '.$name);
        }
        return $filesList[0];
    }
}
