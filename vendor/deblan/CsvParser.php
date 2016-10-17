<?php

namespace Deblan\Csv;

use Deblan\Csv\Exception\CsvParserInvalidParameterException;
use Deblan\Csv\Exception\CsvParserException;

class CsvParser
{
    private $filename;

    private $delimiter;

    private $enclosure;

    private $escapeChar;

    private $hasLegend;

    private $datas = array();

    private $legend = array();

    private $nullValues = array();

    public function __construct($filename, $delimiter = ';', $enclosure = '"', $escapeChar = '\\', $hasLegend = false, array $nullValues = array(''))
    {
        $this->setFilename($filename);
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setEscapeChar($escapeChar);
        $this->setHasLegend($hasLegend);
        $this->setNullValues($nullValues);
    }

    public function setFilename($v)
    {
        if (!is_string($v)) {
            throw new CsvParserInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        if (!file_exists($v)) {
            throw new CsvParserException(sprintf('"%s" does not exist.', $v));
        }

        if (!is_readable($v)) {
            throw new CsvParserException(sprintf('"%s" is not readable.', $v));
        }

        $this->filename = $v;
    }

    public function setDelimiter($v)
    {
        if (!is_string($v)) {
            throw new CsvParserInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->delimiter = $v;
    }

    public function setEnclosure($v)
    {
        if (!is_string($v)) {
            throw new CsvParserInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->enclose = $v;
    }

    public function setEscapeChar($v)
    {
        if (!is_string($v)) {
            throw new CsvParserInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->escapeChar = $v;
    }

    public function setHasLegend($v)
    {
        if (!is_bool($v)) {
            throw new CsvParserInvalidParameterException(sprintf('"%s" is not a valid bool.', $v));
        }

        $this->hasLegend = $v;
    }

    public function getHasLegend()
    {
        return $this->hasLegend;
    }
    
    public function getLegend()
    {
        return $this->legend;
    }

    public function setNullValues(array $v)
    {
        $this->nullValues = $v;
    }

    /**
     *
     * To improve...
     *
     */
    protected function cleanNullValues($line)
    {
        return str_replace($this->nullValues, '', $line);
    }

    public function getDatas()
    {
        return $this->datas;
    }

    public function parse()
    {
        if (!empty($this->datas)) {
            return $this->datas;
        }

        $lines = file($this->filename);

        if (empty($lines)) {
            return true;
        }

        if ($this->hasLegend) {
            $this->legend = str_getcsv($lines[0], $this->delimiter, $this->enclosure, $this->escapeChar);
            unset($lines[0]);
        }

        foreach ($lines as $l => $line) {
            $datas = str_getcsv($this->cleanNullValues($line), $this->delimiter, $this->enclosure, $this->escapeChar);

            if ($this->hasLegend) {
                foreach ($this->legend as $k => $v) {
                    $datas[$v] = isset($datas[$k]) ? $datas[$k] : null;
                }
            }

            $this->datas[] = $datas;
        }

        return true;
    }
}
