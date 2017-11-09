<?php

namespace hiqdev\yii2\export\exporters;

use Box\Spout\Writer\WriterFactory;

class CsvExporter extends AbstractExporter implements ExporterInterface
{
    protected $writer;

    /**
     * Render file content
     *
     * @param $dataProvider
     * @param $columns
     * @return string
     */
    public function export($gird, $columns)
    {
        $this->grid = $gird;
        $this->initExportOptions($columns);

        $this->writer = WriterFactory::create(Type::CSV);
        $this->applySettings();
        ob_start();
        $this->writer->openToBrowser('php://output');

        //header
        $headerRow = $this->generateHeader();
        if (!empty($headerRow)) {
            $this->writer->addRow($headerRow);
        }

        //body
        $bodyRows = $this->generateBody();
        foreach ($bodyRows as $row) {
            $this->writer->addRow($row);
        }

        //footer
        $footerRow = $this->generateFooter();
        if (!empty($footerRow)) {
            $this->writer->addRow($footerRow);
        }

        $this->writer->close();
        $result = ob_get_clean();

        return $result;
    }

    protected function applySettings()
    {
        $this->writer->setFieldDelimiter($this->settings['fieldDelimiter']);
        $this->writer->setFieldEnclosure($this->settings['fieldEnclosure']);
    }
}
