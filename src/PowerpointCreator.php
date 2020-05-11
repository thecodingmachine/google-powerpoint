<?php


namespace TheCodingMachine\GooglePowerpoint;

use TheCodingMachine\GooglePowerpoint\Exceptions\PowerpointException;
use TheCodingMachine\GooglePowerpoint\GoogleAPI\GoogleFileManager;
use TheCodingMachine\GooglePowerpoint\GoogleAPI\GoogleSlideTemporaryFile;
use TheCodingMachine\GooglePowerpoint\TemplateEngine\InjectableVariableInterface;
use TheCodingMachine\GooglePowerpoint\TemplateEngine\TemplateEngine;

final class PowerpointCreator
{
    /**
     * @var TemplateEngine 
     */
    private $templateEngine;
    /**
     * @var GoogleFileManager 
     */
    private $googleFileManager;

    public function __construct(TemplateEngine $templateEngine, GoogleFileManager $googleFileManager)
    {
        $this->templateEngine = $templateEngine;
        $this->googleFileManager = $googleFileManager;
    }

    /**
     * @param InjectableVariableInterface[] $variableList the list of variable to inject
     * @return string the binary data of the generated file
     */
    public function create(iterable $variableList, string $templateFileName): string
    {
        $slideTemporaryFile = $this->googleFileManager->copyTemplate($templateFileName);
        try {
            $this->editPowerpoint($variableList, $slideTemporaryFile);
            $data = $this->googleFileManager->downloadTempFileData($slideTemporaryFile);
            $this->googleFileManager->deleteTempFile($slideTemporaryFile);
            return $data;
        } catch (\Throwable $t) {
            $this->googleFileManager->deleteTempFile($slideTemporaryFile);
            throw $t;
        }
    }

    /**
     * @return void
     */
    /*public function signDocument(GoogleSlideTemporaryFile $temporaryFile, string $imageName)
    {
        $variables = [];
        $image = new PowerpointVariable('signatureImage', 'signatureImage', $imageName);
        $image->setType(2);
        $variables[] = $image;
        $this->editPowerpoint($variables, $temporaryFile);
    }*/

    /**
     * @param InjectableVariableInterface[] $variableList
     */
    private function editPowerpoint(iterable $variableList, GoogleSlideTemporaryFile $slideTemporaryFile): void
    {
        //create the requests
        $requests = [];
        foreach ($variableList as $variable) {
            switch ($variable->getType()) {
                case InjectableVariableInterface::TYPE_TEXT:
                    $requests[] = $this->templateEngine->createTextRequest($variable);
                    break;
                case InjectableVariableInterface::TYPE_PICTURE:
                    $requests[] = $this->templateEngine->createImageRequest($variable);
                    break;
                case InjectableVariableInterface::TYPE_ARRAY:
                    $tableObjectId = $this->googleFileManager->getTableObjectId($slideTemporaryFile, $variable->getVariableName());
                    $requests = array_merge($requests, $this->templateEngine->createArrayRequest($variable, $tableObjectId));
                    break;
                default:
                    throw new PowerpointException('Unknown variable type: '.$variable->getType());
            }
        }
        //send slide api requests
        $this->googleFileManager->editTempFile($slideTemporaryFile, $requests);
    }
}
