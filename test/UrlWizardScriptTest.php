<?php

class UrlWizardScriptTest extends PHPUnit\Framework\TestCase
{
    public function testPdfMetadataFetchUsesExplicitTimeout()
    {
        $script = self::script();

        $this->assertStringContainsString('var PDF_METADATA_TIMEOUT = 30000;', $script);
        $this->assertStringContainsString('timeout: timeout', $script);
        $this->assertStringContainsString("'method': \"pdf-metadata\"", $script);
    }

    public function testPdfMetadataCopyMapsFields()
    {
        $script = self::script();

        $this->assertStringContainsString("['title', 'pub_history_ph_title']", $script);
        $this->assertStringContainsString("['keywords', 'pub_history_ph_keywords']", $script);
        $this->assertStringContainsString("['abstract', 'pub_history_ph_abstract']", $script);
        $this->assertStringContainsString("['copy_notes', 'copy_notes']", $script);
        $this->assertStringContainsString("['copy_credits', 'copy_credits']", $script);
        $this->assertStringContainsString('if (value.length > 0)', $script);
    }

    public function testPdfMetadataFetchIsPdfOnly()
    {
        $script = self::script();

        $this->assertStringContainsString('function url_is_pdf(url)', $script);
        $this->assertStringContainsString('/^[^?#]+\\.pdf([?#].*)?$/i.test(url)', $script);
        $this->assertStringContainsString(
            '(url_is_pdf(url) ? show : hide)("pdf_metadata_fetch_field")',
            $script);
    }

    private static function script()
    {
        return file_get_contents(__DIR__ . '/../public/assets/UrlWizard.js');
    }
}
