<?php

require_once __DIR__ . '/../vendor/autoload.php';

class PdfMetadataParserTest extends PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        foreach ($this->_tempFiles as $tempFile)
        {
            if (file_exists($tempFile))
            {
                unlink($tempFile);
            }
        }
    }

    public function testPopulatedMetadata()
    {
        $fileName = $this->createPdf(array(
            'Title' => 'Parsed Title',
            'Keywords' => 'alpha beta',
            'Subject' => 'Parsed Abstract',
            'Creator' => 'Scan Tool',
            'Author' => 'Manual Scanner'
        ));
        $parser = new Manx\PdfMetadataParser();

        $metadata = $parser->metadataForFile($fileName);

        $this->assertSame('Parsed Title', $metadata['title']);
        $this->assertSame('alpha beta', $metadata['keywords']);
        $this->assertSame('Parsed Abstract', $metadata['abstract']);
        $this->assertSame('Scan Tool', $metadata['copy_notes']);
        $this->assertSame('Manual Scanner', $metadata['copy_credits']);
    }

    public function testEmptyMetadata()
    {
        $fileName = $this->createPdf(array());
        $parser = new Manx\PdfMetadataParser();

        $metadata = $parser->metadataForFile($fileName);

        $this->assertSame(Manx\PdfMetadataParser::emptyMetadata(), $metadata);
    }

    public function testParserFailure()
    {
        $fileName = $this->tempFile();
        file_put_contents($fileName, 'not a pdf');
        $parser = new Manx\PdfMetadataParser();

        $metadata = $parser->metadataForFile($fileName);

        $this->assertSame(Manx\PdfMetadataParser::emptyMetadata(), $metadata);
    }

    private function createPdf(array $details)
    {
        $fileName = $this->tempFile();
        $pdf = "%PDF-1.4\n";
        $offsets = array(0);
        $this->appendObject($pdf, $offsets, 1, "<< /Type /Catalog /Pages 2 0 R >>");
        $this->appendObject($pdf, $offsets, 2, "<< /Type /Pages /Count 0 >>");
        $this->appendObject($pdf, $offsets, 3, $this->infoObject($details));
        $xref = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 4\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 3; $i++)
        {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n";
        $pdf .= "<< /Root 1 0 R /Info 3 0 R /Size 4 >>\n";
        $pdf .= "startxref\n";
        $pdf .= $xref . "\n";
        $pdf .= "%%EOF\n";
        file_put_contents($fileName, $pdf);
        return $fileName;
    }

    private function appendObject(&$pdf, array &$offsets, $number, $body)
    {
        $offsets[$number] = strlen($pdf);
        $pdf .= $number . " 0 obj\n";
        $pdf .= $body . "\n";
        $pdf .= "endobj\n";
    }

    private function infoObject(array $details)
    {
        $parts = array();
        foreach ($details as $key => $value)
        {
            array_push($parts, '/' . $key . ' (' . self::pdfString($value) . ')');
        }
        return "<< " . implode(' ', $parts) . " >>";
    }

    private static function pdfString($value)
    {
        return str_replace(
            array('\\', '(', ')'),
            array('\\\\', '\\(', '\\)'),
            $value);
    }

    private function tempFile()
    {
        $fileName = tempnam(sys_get_temp_dir(), 'manx-test-pdf-');
        array_push($this->_tempFiles, $fileName);
        return $fileName;
    }

    private $_tempFiles = array();
}
