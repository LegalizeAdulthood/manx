<?php

namespace Manx;

require_once __DIR__ . '/../vendor/autoload.php';

class RssWriter
{ 
    private $_dateTimeProvider;
    private $_writer;

    public function __construct(IDateTimeProvider $dateTimeProvider, $xsltFilePath = '')
    {
        $this->_dateTimeProvider = $dateTimeProvider;
        $this->_writer = new \XMLWriter();
        $this->_writer->openMemory();
        $this->_writer->setIndent(true);
        $this->_writer->setIndentString('  ');
        $this->_writer->startDocument('1.0', 'UTF-8');

        if ($xsltFilePath)
        {
            $this->_writer->writePi('xml-stylesheet', sprintf('type="text/xsl" href="%s"', $xsltFilePath));
        }

        $this->_writer->startElement('rss');
        $this->_writer->writeAttribute('version', '2.0');
    }

    public function beginChannel($title, $link, $description)
    {
        $this->_writer->startElement('channel');
        $this->_writer->writeElement('title', $title);
        $this->_writer->writeElement('link', $link);
        $this->_writer->writeElement('description', $description);
        $now = $this->_dateTimeProvider->now();
        $this->_writer->writeElement('lastBuildDate', $now->format(\DateTime::RFC1123));
        return $this;
    }

    public function language($lang)
    {
        $this->_writer->writeElement('language', $lang);
        return $this;
    }

    public function endChannel()
    {
        $this->_writer->endElement();
        return $this;
    }

    public function item($title, $link, $description, $optional = array())
    {
        $this->_writer->startElement('item');
        $this->_writer->writeElement('title', $title);
        $this->_writer->writeElement('link', $link);
        $this->_writer->writeElement('description', $description);
        $this->fromArray($optional);
        $this->_writer->endElement();
    }

    private function fromArray($content)
    {
        if (is_array($content))
        {
            foreach ($content as $index => $element)
            {
                if (is_array($element))
                {
                    $this->_writer->startElement($index);
                    $this->fromArray($element);
                    $this->_writer->endElement();
                }
                else
                {
                    $this->_writer->writeElement($index, $element);
                }
            }
        }
    }

    public function getDocument()
    {
        $this->_writer->endElement();
        $this->_writer->endDocument();
        return $this->_writer->outputMemory();
    }

    public function output()
    {
        $this->renderHeader();
        $this->renderBody();
    }

    public function renderHeader()
    {
        header('Content-type: text/xml');
    }

    public function renderBody()
    {
        echo $this->getDocument();
    }
} 
