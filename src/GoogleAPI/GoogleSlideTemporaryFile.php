<?php


namespace TheCodingMachine\GooglePowerpoint\GoogleAPI;

//this class represent a file in google drive copied from a template
//Use for powerpoint generation
class GoogleSlideTemporaryFile
{
    /** @var string  */
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }
}
