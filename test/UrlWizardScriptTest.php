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
        $this->assertStringContainsString(
            '$("#pdf_metadata_" + source + "_copy").prop("checked")',
            $script);
        $this->assertStringContainsString(
            '$("#pdf_metadata_" + source + "_copy").prop("checked", true)',
            $script);
    }

    public function testPdfMetadataFetchIsPdfOnly()
    {
        $script = self::script();

        $this->assertStringContainsString('function url_is_pdf(url)', $script);
        $this->assertStringContainsString('/^[^?#]+\\.pdf([?#].*)?$/i.test(url)', $script);
        $this->assertStringContainsString(
            '(url_is_pdf(url) ? show : hide)("pdf_metadata_fetch")',
            $script);
    }

    public function testCachedPdfMetadataIsLoadedForCopy()
    {
        $script = self::script();

        $this->assertStringContainsString(
            'function load_cached_pdf_metadata()',
            $script);
        $this->assertStringContainsString(
            'var cached = $("#cached_pdf_metadata")',
            $script);
        $this->assertStringContainsString(
            'pdf_metadata = JSON.parse(cached.text())',
            $script);
        $this->assertStringContainsString(
            'load_cached_pdf_metadata();',
            $script);
    }

    public function testPublicationSearchFailuresReplaceWorkingIndicator()
    {
        $script = self::script();

        $this->assertStringContainsString(
            'register_ajax_error_handler(error_id, working_id);',
            $script);
        $this->assertStringContainsString('show_working(working_id);',
            $script);
        $this->assertStringContainsString(
            'show_request_error(working_id, request_failed_message(response));',
            $script);
        $this->assertStringContainsString(
            '"Request failed with HTTP status " + response.status + "."',
            $script);
        $this->assertStringContainsString(
            '$("#" + id).removeClass("working").addClass("error");',
            $script);
    }

    private static function script()
    {
        return file_get_contents(__DIR__ . '/../public/assets/UrlWizard.js');
    }
}
