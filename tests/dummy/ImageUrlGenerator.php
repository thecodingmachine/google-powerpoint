<?php


namespace TheCodingMachine\GooglePowerpoint\Tests\dummy;

use TheCodingMachine\GooglePowerpoint\TemplateEngine\ImageUrlStrategy;
use TheCodingMachine\GooglePowerpoint\TemplateEngine\InjectableVariableInterface;

class ImageUrlGenerator implements ImageUrlStrategy
{
    public function getUrlForImage(InjectableVariableInterface $variable): string
    {
        return 'wwww.placeholder.url.fr/'.$variable->getValue();
    }
}
