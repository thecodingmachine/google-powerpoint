<?php


namespace TheCodingMachine\GooglePowerpoint\Powerpoint;

//represent a variable to create during the powerpoint generation. Can represent a text value, picture filepath, or a list of row to insert in a table.
interface InjectableVariableInterface
{
    public const TYPE_TEXT = 1;
    public const TYPE_PICTURE = 2;
    public const TYPE_ARRAY = 3;
    
    public function getVariableName(): string;

    public function getValue(): string;
    
    public function getType(): int;
}
