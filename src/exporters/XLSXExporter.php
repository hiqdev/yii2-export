<?php

declare(strict_types=1);


namespace hiqdev\yii2\export\exporters;

use hipanel\grid\ColspanColumn;
use OpenSpout\Writer\Exception\WriterNotOpenedException;
use OpenSpout\Writer\WriterInterface;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Properties;
use OpenSpout\Writer\XLSX\Writer;
use Yii;

class XLSXExporter extends AbstractExporter
{
    private ?Options $options = null;

    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getExportType(): ExportType
    {
        return ExportType::XLSX;
    }

    protected function getWriter(): ?WriterInterface
    {
        $this->initOptions();

        return new Writer($this->options);
    }

    private function initOptions(): void
    {
        $options = new Options();
        $options->setProperties($this->getProperties());

        $this->options = $options;
    }

    /**
     * @throws WriterNotOpenedException
     */
    protected function beforeClose(Writer|WriterInterface $writer): void
    {
        if ($this->hasColspanColumn) {
            $this->mergeColspanColumns($writer);
        }
    }

    /**
     * @throws WriterNotOpenedException
     */
    private function mergeColspanColumns(Writer|WriterInterface $writer): void
    {
        $topLeftColumn = 0;
        $sheetIndex = $writer->getCurrentSheet()->getIndex();

        foreach ($this->grid->columns as $column) {
            if ($column instanceof ColspanColumn) {
                $subColumnCount = count($column->columns);
                $bottomRightColumn = $topLeftColumn + $subColumnCount - 1;
                $this->options->mergeCells($topLeftColumn, 1, $bottomRightColumn, 1, $sheetIndex);
                $topLeftColumn += $subColumnCount;
            } else {
                $topLeftColumn++;
            }
        }
    }

    private function getProperties(): Properties
    {
        $orgName = Yii::$app->params['organization.name'] ?? '';

        return new Properties(
            title: 'HiPanel Report',
            application: 'Yii2 Export',
            creator: $orgName,
            lastModifiedBy: $orgName,
        );
    }
}
