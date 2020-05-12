<?php

namespace TheCodingMachine\GooglePowerpoint\Tests;

use PHPUnit\Framework\TestCase;
use TheCodingMachine\GooglePowerpoint\TemplateEngine\InjectableVariableInterface;
use TheCodingMachine\GooglePowerpoint\TemplateEngine\TemplateEngine;
use TheCodingMachine\GooglePowerpoint\Tests\dummy\DummyVariable;
use TheCodingMachine\GooglePowerpoint\Tests\dummy\ImageUrlGenerator;

class TemplateEngineTest extends TestCase
{
    /**
     * @var TemplateEngine
     */
    private $engine;
    /**
     * @var ImageUrlGenerator
     */
    private $imageUrlStartegy;

    protected function setup(): void
    {
        $this->imageUrlStartegy = new ImageUrlGenerator();
        $this->engine = new TemplateEngine($this->imageUrlStartegy);
    }
    
    public function testInsertTextRequest(): void
    {
        $v = new DummyVariable('testName', 'hello', InjectableVariableInterface::TYPE_TEXT);
        
        $request = $this->engine->createTextRequest($v);
        $expected = [
            'replaceAllText' => [
                'containsText' => [
                    'text' => '{testName}',
                    'matchCase' => true
                ],
                'replaceText' => 'hello'
            ]
        ];
        $this->assertEquals($expected, $request);
    }

    public function testInsertImageRequest(): void
    {
        $v = new DummyVariable('testName', 'hello.png', InjectableVariableInterface::TYPE_PICTURE);
        
        $request = $this->engine->createImageRequest($v);
        $expected = [
            'replaceAllShapesWithImage' => [
                'imageUrl' => $this->imageUrlStartegy->getUrlForImage($v),
                'replaceMethod' => 'CENTER_CROP',
                'containsText' => [
                    'text' => '{testName}',
                    'matchCase' => true
                ]
            ]
        ];
        $this->assertEquals($expected, $request);
    }

    //todo more tests for the array case especially the requests pagination
    public function testInsertArrayRequest(): void
    {
        $value = [['a', 1], [1, 2]];
        /** @var string $encoded */
        $encoded = json_encode($value);
        $v = new DummyVariable('testName', $encoded, InjectableVariableInterface::TYPE_ARRAY);

        $request = $this->engine->createArrayRequest($v, 'dummyId');
        $this->assertCount(5, $request);
        $insertTableRowsRequest = [
            'insertTableRows' => [
                'tableObjectId' => 'dummyId',
                'cellLocation' => [
                    'rowIndex' => 1,
                ],
                'insertBelow' => true,
                'number' => 2,
            ]
        ];
        $this->assertEquals($insertTableRowsRequest, $request[0]);
        $insertText1Request = [
            'insertText' => [
                'objectId' => 'dummyId',
                'cellLocation' => [
                    'rowIndex' => 1,
                    'columnIndex' => 0,
                ],
                'text' => 'a',
                'insertionIndex' => 0,
            ]
        ];
        $this->assertEquals($insertText1Request, $request[1]);
        $insertText3Request = [
            'insertText' => [
                'objectId' => 'dummyId',
                'cellLocation' => [
                    'rowIndex' => 2,
                    'columnIndex' => 0,
                ],
                'text' => '1',
                'insertionIndex' => 0,
            ]
        ];
        $this->assertEquals($insertText3Request, $request[3]);
    }
}
