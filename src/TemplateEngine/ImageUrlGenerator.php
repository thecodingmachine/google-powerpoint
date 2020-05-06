<?php


namespace TheCodingMachine\GooglePowerpoint\Powerpoint;

class ImageUrlGenerator
{
    public function getUrlForImage(InjectableVariableInterface $variable): string
    {
        if (getenv('APP_ENV') === 'dev') {
            // return 'https://' . getenv('AWS_S3_BUCKET') . '.s3-' . getenv('AWS_DEFAULT_REGION') . '.amazonaws.com/' . $variable->getValue();
            return 'https://via.placeholder.com/1024x768?text=Image%20Coming%20Soon';
        }
        return getenv('FULL_URL') . 'powerpoint/variable/see/' . $variable->getValue();
    }
}
