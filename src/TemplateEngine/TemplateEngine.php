<?php


namespace TheCodingMachine\GooglePowerpoint\Powerpoint;

class TemplateEngine
{
    const MAX_ROWS_BY_REQUEST = 20;
    /** @var ImageUrlGenerator  */
    private $imageUrlGenerator;

    public function __construct(ImageUrlGenerator $imageUrlGenerator)
    {
        $this->imageUrlGenerator = $imageUrlGenerator;
    }

    public function createTextRequest(InjectableVariableInterface $variable): array
    {
        return [
            'replaceAllText' => [
                'containsText' => [
                    'text' => '{' .$variable->getVariableName(). '}',
                    'matchCase' => true
                ],
                'replaceText' => $variable->getValue()
            ]
        ];
    }

    public function createImageRequest(InjectableVariableInterface $variable): array
    {
        return [
            'replaceAllShapesWithImage' => [
                'imageUrl' => $this->imageUrlGenerator->getUrlForImage($variable),
                'replaceMethod' => 'CENTER_CROP',
                'containsText' => [
                    'text' => '{'.$variable->getVariableName().'}',
                    'matchCase' => true
                ]
            ]

        ];
    }

    public function createArrayRequest(InjectableVariableInterface $variable, string $tableObjectId): array
    {
        $requests = [];
        //get the data as an array
        $arrayData = json_decode($variable->getValue(), true);

        //create as many rows in the table as needed (we are limited to 20 rows by requests)
        $totalNumberOfRows = count($arrayData);
        $numberOfIterations = 0;
        do {
            if ($numberOfIterations > 50) {
                throw new \RuntimeException('Infinite loop?');
            }
            $numberOfRows = $totalNumberOfRows - self::MAX_ROWS_BY_REQUEST < 0 ? $totalNumberOfRows : self::MAX_ROWS_BY_REQUEST;
            $totalNumberOfRows -= self::MAX_ROWS_BY_REQUEST;

            $requests[] = [
                'insertTableRows' => [
                    'tableObjectId' => $tableObjectId,
                    'cellLocation' => [
                        'rowIndex' => $numberOfIterations * self::MAX_ROWS_BY_REQUEST + 1,
                    ],
                    'insertBelow' => true,
                    'number' => $numberOfRows,
                ]
            ];
            $numberOfIterations++;
        } while ($totalNumberOfRows > 0);
        
        foreach ($arrayData as $i => $arrayRow) {
            foreach ($arrayRow as $j => $arrayValue) {
                if ($arrayValue === null) {
                    continue; //ignore cells with the value null
                }
                //for each cell in the new table row, inject the correct text
                $requests[] = [
                    'insertText' => [
                        'objectId' => $tableObjectId,
                        'cellLocation' => [
                            'rowIndex' => $i + 1,
                            'columnIndex' => $j,
                        ],
                        'text' => (string) $arrayValue,
                        'insertionIndex' => 0,
                    ]
                ];
            }
        }
        return $requests;
    }
}
