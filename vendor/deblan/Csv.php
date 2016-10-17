<?php

namespace Deblan\Csv;

use Deblan\Csv\Exception\CsvInvalidParameterException;

class Csv
{
    private $delimiter;

    private $enclosure;

    private $endline;

    private $datas;

    private $legend;

    private $render;

    private $encoding;

    private $hasLegend = false;

    public function __construct($delimiter = ';', $enclosure = '"', $endline = "\n", $encoding = 'UTF-8')
    {
        $this->setDelimiter($delimiter);
        $this->setEnclosure($enclosure);
        $this->setEndLine($endline);
        $this->setEncoding($encoding);
        $this->datas  = array(0 => null);
        $this->legend = array();
        $this->render = "";
    }

    public function setFilename($v)
    {
        if (!is_string($v)) {
            throw new CsvInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->filename = $v;
    }

    protected function setHasLegend($hasLegend)
    {
        $this->hasLegend = $hasLegend;

        return $this;
    }

    public function getHasLegend()
    {
        return $this->hasLegend;
    }

    public function hasLegend()
    {
        return $this->hasLegend;
    }

    public function setLegend(array $values)
    {
        $this->setHasLegend(true);

        $this->legend = $values;

        $this->addLine($values, 0);
    }

    public function addLine(array $values, $key = null)
    {
        if ($key !== null) {
            $this->datas[$key] = $values;

            return true;
        }

        $this->datas[] = $values;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setDelimiter($v)
    {
        if (!is_string($v)) {
            throw new CsvInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->delimiter = $v;
    }

    public function setEndline($v)
    {
        if (!is_string($v)) {
            throw new CsvInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->endline = $v;
    }

    public function setEnclosure($v)
    {
        if (!is_string($v)) {
            throw new CsvInvalidParameterException(sprintf('"%s" is not a valid string.', $v));
        }

        $this->enclose = $v;
    }

    public function getLegend()
    {
        return $this->legend;
    }

    public function getDatas()
    {
        return $this->datas;
    }

    public function compile()
    {
        $this->render = "";

        if ($this->datas[0] !== null) {
            $this->append($this->datasToCsvLine($this->datas[0]));
        }

        unset($this->datas[0]);

        foreach ($this->datas as $v) {
            $this->append($this->datasToCsvLine($v));
        }

        if ($this->encoding !== 'UTF-8') {
            $this->render = iconv(
                mb_detect_encoding($this->render),
                $this->encoding,
                $this->render
            );
        }

        return $this->render;
    }

    public function hasDatas()
    {
        return count($this->datas) > 1;
    }

    protected function datasToCsvLine($datas)
    {
        foreach ($datas as $k => $v) {
            $v = str_replace('\\', '\\\\', $v);

            if ($this->enclose) {
                $v = str_replace($this->enclose, '\\'.$this->enclose, $v);
            } else {
                $v = str_replace($this->delimiter, '\\'.$this->delimiter, $v);
            }

            $datas[$k] = $this->enclose.$v.$this->enclose;
        }

        $datas = implode($this->delimiter, $datas);

        return $datas;
    }

    protected function append($line)
    {
        $this->render.= sprintf("%s%s", $line, $this->endline);
    }

    public function compileToFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }

        file_put_contents($filename, $this->compile());
    }
}
