<?php

class GenerateTable
{

    const COUNT_SPACE = 4;
    const NUMBERING = '№';

    /**
     * @var int
     */
    private $countArrayKey;
    /**
     * @var array
     */
    private $data;
    /**
     * @var array
     */
    private $lengths;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->check();
        $this->lengths();
    }

    private function lengths(): void
    {
        $lengths = [];
        foreach ($this->data as $key => $column) {
            $length = array_map('iconv_strlen', $column);
            $length[] = iconv_strlen($key);
            $lengths[$key] = max($length);
        }
        $this->lengths = $lengths;
    }

    private function values(): array
    {
        $values = [];
        foreach ($this->data as $d) {
            $values[] = array_values($d);
        }
        $arrValues = [];
        for ($i = 0; $i < $this->countArrayKey; $i++) {
            for ($j = 0; $j < count($values); $j++) {
                $arrValues[$i][] = $values[$j][$i];
            }
        }
        return $arrValues;
    }

    private function space(int $length, int $maxLength): array
    {
        $count = ($maxLength + (self::COUNT_SPACE * 2) - $length) / 2;
        return [
            'left' => ($length < $maxLength) ? floor($count) : self::COUNT_SPACE,
            'right' => ($length < $maxLength) ? ceil($count) : self::COUNT_SPACE
        ];
    }

    private function delimiter(bool $firstOrLast = false): string
    {
        $delimiter = "|";
        foreach ($this->lengths as $key => $length) {
            for ($i = 0; $i < ($length + (self::COUNT_SPACE * 2)); $i++) {
                $delimiter .= "-";
            }
            $delimiter .= $firstOrLast ? "-" : "+";
        }
        return substr_replace($delimiter, "|", -1);
    }

    private function cell(string &$string, array $count, string $text)
    {
        for ($i = 0; $i < $count['left']; $i++) {
            $string .= " ";
        }
        $string .= $text;
        for ($i = 0; $i < $count['right']; $i++) {
            $string .= " ";
        }
    }

    private function head(): string
    {
        $head = $this->delimiter(true) . "\n|";
        foreach ($this->data as $key => $item) {
            $space = $this->space(iconv_strlen($key), $this->lengths[$key]);
            $this->cell($head, $space, $key);
            $head .= "|";
        }
        return $head . "\n" . $this->delimiter() . "\n";
    }

    private function body(): string
    {
        $values = $this->values();
        $keys = array_keys($this->data);
        $body = "";
        $iteration = 0;
        foreach ($values as $value) {
            $iteration++;
            $body .= "|";
            for ($i = 0; $i < count($value); $i++) {
                $space = $this->space(iconv_strlen($value[$i]), $this->lengths[$keys[$i]]);
                $this->cell($body, $space, $value[$i]);
                $body .= "|";
            }
            $body .= "\n" . $this->delimiter(($iteration === count($values))) . "\n";
        }
        return $body;
    }

    private function numbering(int $count): array
    {
        $number = [];
        for ($i = 1; $i <= $count; $i++) {
            $number[] = $i;
        }
        return $number;
    }

    private function check(): void {
        $this->countArrayKey = max(array_map('count', $this->data));
        foreach($this->data as $key => $data) {
            $count = $this->countArrayKey - count($data);
            for($i = 0; $i <= $count; $i++) {
                $this->data[$key][] = " ";
            }
        }
    }

    public function execute(bool $numbering = false, string $filename = null): string
    {
        if ($numbering) {
            $this->data = array_merge(
                [self::NUMBERING => $this->numbering($this->countArrayKey)],
                $this->data
            );
            $this->lengths();
        }
        $table = $this->head() . $this->body();
        if ($filename) file_put_contents($filename . ".txt", $table);
        return $table;
    }
}
