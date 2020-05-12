<?php

namespace TheCodingMachine\GooglePowerpoint\TemplateEngine;


use TheCodingMachine\GooglePowerpoint\TemplateEngine\InjectableVariableInterface;

//Use this interface to explicit your logic concerning your images' url.
interface ImageUrlStrategy
{
    public function getUrlForImage(InjectableVariableInterface $variable): string;
}