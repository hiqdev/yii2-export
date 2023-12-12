<?php

declare(strict_types=1);

namespace hiqdev\yii2\export\exporters;

use hiqdev\yii2\export\models\SaveManager;

class MDExporter extends AbstractExporter
{
    public Type $exportType = Type::MD;

    public function getMimeType(): string
    {
        return 'text/plain';
    }

    public function export(SaveManager $saveManager): void
    {
        $fileName = $saveManager->getFilePath();
        $rows = [];
        $header = $this->generateHeader();
        $batches = $this->generateBody();
        foreach ($batches as $batch) {
            $rows = [...$rows, ...$batch];
        }
        $widths = $this->calculateWidths([$header, ...$rows]);
        $mdTable = $this->renderHeader($header, $widths);
        $mdTable .= $this->renderRows($rows, $widths);
        file_put_contents($fileName, $mdTable);
    }

    protected function renderHeader(array $header, array $widths): string
    {
        $result = '| ';
        for ($i = 0, $iMax = count($header); $i < $iMax; $i++) {
            $result .= $this->renderCell($header[$i], $widths[$i]) . ' | ';
        }

        return rtrim($result, ' ') . PHP_EOL . $this->renderDelimiter($widths) . PHP_EOL;
    }

    protected function renderRows(array $rows, array $widths): string
    {
        $result = '';
        foreach ($rows as $row) {
            $result .= '| ';
            for ($i = 0, $iMax = count($row); $i < $iMax; $i++) {
                $result .= $this->renderCell($row[$i], $widths[$i]) . ' | ';
            }
            $result = rtrim($result, ' ') . PHP_EOL;
        }

        return $result;
    }

    protected function renderCell($contents, $width): string
    {
        return str_pad($contents, $width);
    }

    protected function renderDelimiter($widths): string
    {
        $row = '|';
        foreach ($widths as $iValue) {
            $cell = str_repeat('-', $iValue + 2);

            $row .= $cell . '|';
        }

        return $row;
    }

    protected function calculateWidths(array $rows = []): array
    {
        $widths = [];

        foreach ($rows as $row) {
            for ($i = 0, $iMax = count($row); $i < $iMax; $i++) {
                $iWidth = strlen((string)$row[$i]);
                if ((!array_key_exists($i, $widths)) || $iWidth > $widths[$i]) {
                    $widths[$i] = $iWidth;
                }
            }
        }

        // all columns must be at least 3 wide for the markdown to work
        return array_map(static function ($width) {
            return max($width, 3);
        }, $widths);
    }
}
