<?php


namespace TheCodingMachine\GooglePowerpoint\Tests\dummy;

use TheCodingMachine\GooglePowerpoint\TemplateEngine\InjectableVariableInterface;

class DummyVariable implements InjectableVariableInterface
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $value;
    /**
     * @var int
     */
    private $type;

    public function __construct(string $name, string $value, int $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    public function getVariableName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
