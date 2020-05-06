<?php


namespace TheCodingMachine\GooglePowerpoint\GoogleAPI;


class GoogleFileManager
{

    private Google_Service_Drive $googleDriveService;
    private Google_Service_Slides $googleSlideService;

    public function __construct(GoogleClientFactory $googleClientFactory)
    {
        $this->googleDriveService = $googleClientFactory->getDriveService();
        $this->googleSlideService = $googleClientFactory->getSlideService();
    }
    
    public function initTemplateFileFromName(string $templateName): GoogleTemplateFileInterface
    {
        $id = $this->getByName($templateName)->getId();
        return $this->initClass($templateName, $id);
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
        $fileMetadata = new Google_Service_Drive_DriveFile(['name' => $name, 'mimeType' => 'application/vnd.google-apps.presentation']);
        $fileMetadata->setParents(['1gxG5S7nO34EnH20tCxPgihi4HBcgC9Dh']); //todo remove this line in the final version
        $this->googleDriveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'uploadType' => 'multipart',
            'fields' => 'id']);
    }

    public function uploadNewTemplate(string $templateName, string $content): GoogleTemplateFileInterface
    {
        try {
            //delete old file
            $id = $this->getByName($templateName)->getId();
            $this->googleDriveService->files->delete($id);
        } catch (\Exception $e) {
            //do nothing if no old file is found
        }
        //upload template file to ggogle drive and get its id.
        $fileMetadata = new Google_Service_Drive_DriveFile(['name' => $templateName, 'mimeType' => 'application/vnd.google-apps.presentation']);
        $fileMetadata->setParents(['1gxG5S7nO34EnH20tCxPgihi4HBcgC9Dh']); //todo remove this line in the final version
        $file = $this->googleDriveService->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'uploadType' => 'multipart',
            'fields' => 'id']);
        return $this->initClass($templateName, $file->getId());
    }

    public function copyTemplate(GoogleTemplateFileInterface $template): GoogleSlideTemporaryFile
    {
        $templateId = $template->getId();
        $copy = new Google_Service_Drive_DriveFile([
            'name' => 'editedPresentation'
        ]);
        $id = $this->googleDriveService->files->copy($templateId, $copy, ['fields' => 'id'])->getId();
        return new GoogleSlideTemporaryFile($id);
    }
    
    public function editTempFile(GoogleSlideTemporaryFile $slideTemporaryFile, array $requests): void
    {
        $batchUpdateRequest = new Google_Service_Slides_BatchUpdatePresentationRequest(['requests' => $requests]);
        $this->googleSlideService->presentations->batchUpdate($slideTemporaryFile->getId(), $batchUpdateRequest);
    }

    public function downloadTempFileData(GoogleSlideTemporaryFile $presentation): string
    {
        /** @var Response $response */
        $response = $this->googleDriveService->files->export($presentation->getId(), 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
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
    
    private function initClass(string $templateName, string $id): GoogleTemplateFileInterface
    {
        switch ($templateName) {
            case POCTemplateFile::NAME:
                return new POCTemplateFile($id);
            case BillingTemplateFile::NAME:
                return new BillingTemplateFile($id);
            case QuoteTemplateFile::NAME:
                return new QuoteTemplateFile($id);
            case LegalFolderReportTemplateFile::NAME:
                return new LegalFolderReportTemplateFile($id);
            case SettledFolderReportTemplateFile::NAME:
                return new SettledFolderReportTemplateFile($id);
            //todo add other template cases
            default:
                throw new GoogleApiException("Could not init template file $templateName");
        }
    }
}
