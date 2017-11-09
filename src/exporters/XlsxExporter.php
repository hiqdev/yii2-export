<?php

namespace hiqdev\yii2\export\exporters;

use Box\Spout\Writer\WriterFactory;

class XlsxExporter extends AbstractExporter implements ExporterInterface
{
    /**
     * Render file content
     *
     * @return string
     */
    public function export($dataProvider, $columns)
    {
        $this->initExportOptions($dataProvider, $columns);

        $writer = WriterFactory::create(Type::XLSX);
        ob_start();
        $writer->openToBrowser('php://output');

        //header
        $headerRow = $this->generateHeader();
        if (!empty($headerRow)) {
            $writer->addRow($headerRow);
        }

        //body
        $bodyRows = $this->generateBody();
        foreach ($bodyRows as $row) {
            $writer->addRow($row);
        }

        //footer
        $footerRow = $this->generateFooter();
        if (!empty($footerRow)) {
            $writer->addRow($footerRow);
        }

        $writer->close();
        $result = ob_get_clean();

        return $result;
    }
}
