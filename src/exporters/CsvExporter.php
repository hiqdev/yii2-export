<?php

namespace hiqdev\yii2\export\exporters;

use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;
use Box\Spout\Writer\WriterFactory;

class CsvExporter extends AbstractExporter implements ExporterInterface
{
    protected $writer;

    /**
     * Render file content
     *
     * @param $grid
     * @return string
     * @throws IOException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function export($grid): string
    {
        $this->initExportOptions($grid);

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

        return ob_get_clean();
    }

    protected function applySettings()
    {
        $this->writer->setFieldDelimiter($this->settings['fieldDelimiter']);
        $this->writer->setFieldEnclosure($this->settings['fieldEnclosure']);
    }
}
