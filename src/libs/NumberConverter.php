<?php

namespace Basttyy\FxDataServer\libs;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class NumberConverter
{
    /**
     * Error codes.
     *
     * @var array
     */
    private static $error_codes = [
        101 => 'Error: Please insert a valid number.',
        102 => 'Error: 2nd parameter is required. Ex. convert(21,R).',
        103 => "Error: Invalid 2nd parameter. Use 'R' for roman, 'W' for words,
                'N' for number ordinals, and 'O' for worded ordinal.",
        104 => 'Error: Only integers from 1 to 3999 are convertible into Roman.',
        105 => 'Error: Word converter accepts numbers between -'.PHP_INT_MAX.' and '.PHP_INT_MAX,
        106 => "Error: Negative numbers can't have ordinal suffix.",
    ];

    /**
     * Small number string matches.
     *
     * @var string
     */
    private $small_number_string = [
        0  => 'zero',
        1  => 'first',
        2  => 'second',
        3  => 'third',
        4  => 'fourth',
        5  => 'fifth',
        6  => 'sixth',
        7  => 'seventh',
        8  => 'eighth',
        9  => 'ninth',
        10 => 'tenth',
        11 => 'eleventh',
        12 => 'twelfth',
        13 => 'thirteen',
        14 => 'fourteenth',
        15 => 'fifteenth',
        16 => 'sixteenth',
        17 => 'seventeenth',
        18 => 'eighteenth',
        19 => 'nineteenth',
    ];

    /**
     * Word dictionary.
     *
     * @var array
     */
    private $word_dictionary = [
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'fourty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion',
    ];

    /**
     * Covert provided number to requested type.
     *
     * @param string $number
     * @param string $type
     *
     * @return string
     */
    public function convert($number = '', $type = '')
    {
        if (strlen($type) > 1) {
            $type = $type[0];
        }

        switch (strtolower($type)) {
            case 'w':
                return $this->convertToWord($number);
            case 'o':
                return $this->convertToOrdinal($number);
            case 'n':
                return $this->convertToNumberOrdinal($number);
            case 'r':
                return $this->convertToRoman($number);
        }
    }

    /**
     * Check provided paramaters.
     *
     * @param int    $number
     * @param string $type
     *
     * @return int
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function checkParamaters($number, $type)
    {
        if ($number === '' || !is_numeric($number)) {
            return 101;
        }

        if ($type === '') {
            return 102;
        }

        if (!in_array($type, ['w', 'o', 'n', 'r'])) {
            return 103;
        }

        if ($type == 'r' && (!is_int($number) || $number < 1 || $number > 3999)) {
            return 104;
        }

        if ($number > PHP_INT_MAX || $number < 0 - PHP_INT_MAX) {
            return 105;
        }

        if ($type == 'o' && $number < 0) {
            return 106;
        }

        return 0;
    }

    /**
     * Convert number to an ordinal (numerals and letter suffixes).
     *
     * @param int $number
     *
     * @return string
     */
    private function convertToNumberOrdinal($number)
    {
        $error_code = $this->checkParamaters($number, 'n');

        if ($error_code > 0) {
            return self::$error_codes[$error_code];
        }

        if ($number === 0) {
            return 0;
        }

        if (!in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return $number.'st';
                case 2:
                    return $number.'nd';
                case 3:
                    return $number.'rd';
            }
        }

        return $number.'th';
    }

    /**
     * Alias to convertToOrdinal.
     *
     * @param int $number
     *
     * @return string
     */
    public function numberOrdinal($number)
    {
        return $this->convertToNumberOrdinal($number);
    }

    /**
     * Convert number to an ordinal (letters only).
     *
     * @param int $number
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function convertToOrdinal($number)
    {
        $error_code = $this->checkParamaters($number, 'o');

        if ($error_code > 0) {
            return self::$error_codes[$error_code];
        }

        $small_number = (int) substr($number, strlen($number) - 1);
        $big_number = (int) ($number - $small_number);

        $string = '';

        // Numbers between 20 and 99.
        if ($big_number > 0 && $number > 19 && $number < 100) {
            return $this->convertToWord($big_number).' '.$this->convertToOrdinal($small_number);
        }

        // Numbers over 100.
        if ($number >= 100) {
            $string = $this->convertToWord(str_pad(substr($number, 0, 1), strlen($number), '0')).'th';

            if ($small_number > 0) {
                $string .= ' and '.$this->convertToWord($small_number);
            }

            return $string;
        }

        return $this->small_number_string[$number];
    }

    /**
     * Alias to convertToOrdinal.
     *
     * @param int $number
     *
     * @return string
     */
    public function ordinal($number)
    {
        return $this->convertToOrdinal($number);
    }

    /**
     * Alias to convertToOrdinal.
     *
     * @param int $number
     *
     * @return string
     */
    public function wordOrdinal($number)
    {
        return $this->convertToOrdinal($number);
    }

    /**
     * Convert number to a roman numeral.
     *
     * @param int $number
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function convertToRoman($number)
    {
        $error_code = $this->checkParamaters($number, 'r');

        if ($error_code > 0) {
            return self::$error_codes[$error_code];
        }

        $roman_number = '';

        while ($number >= 1000) {
            $roman_number .= 'M';
            $number -= 1000;
        }

        while ($number >= 900) {
            $roman_number .= 'CM';
            $number -= 900;
        }

        while ($number >= 500) {
            $roman_number .= 'D';
            $number -= 500;
        }

        while ($number >= 400) {
            $roman_number .= 'CD';
            $number -= 400;
        }

        while ($number >= 100) {
            $roman_number .= 'C';
            $number -= 100;
        }

        while ($number >= 90) {
            $roman_number .= 'XC';
            $number -= 90;
        }

        while ($number >= 50) {
            $roman_number .= 'L';
            $number -= 50;
        }

        while ($number >= 40) {
            $roman_number .= 'XL';
            $number -= 40;
        }

        while ($number >= 10) {
            $roman_number .= 'X';
            $number -= 10;
        }

        while ($number >= 9) {
            $roman_number .= 'IX';
            $number -= 9;
        }

        while ($number >= 5) {
            $roman_number .= 'V';
            $number -= 5;
        }

        while ($number >= 4) {
            $roman_number .= 'IV';
            $number -= 4;
        }

        while ($number >= 1) {
            $roman_number .= 'I';
            $number -= 1;
        }

        return $roman_number;
    }

    /**
     * Alias to convertToRoman.
     *
     * @param int $number
     *
     * @return string
     */
    public function roman($number)
    {
        return $this->convertToRoman($number);
    }

    /**
     * Convert number to a word.
     *
     * @param int $number
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function convertToWord($number)
    {
        $error_code = $this->checkParamaters($number, 'w');

        if ($error_code > 0) {
            return self::$error_codes[$error_code];
        }

        $hyphen = '-';
        $conjunction = ' and ';
        $separator = ', ';
        $negative = 'negative ';
        $decimal = ' point ';

        if ($number < 0) {
            return $negative.$this->convertToWord(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $this->word_dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int) ($number / 10)) * 10;
                $units = $number % 10;
                $string = $this->word_dictionary[$tens];
                if ($units) {
                    $string .= $hyphen.$this->word_dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $this->word_dictionary[$hundreds].' '.$this->word_dictionary[100];
                if ($remainder) {
                    $string .= $conjunction.$this->convertToWord($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convertToWord($numBaseUnits).' '.$this->word_dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convertToWord($remainder);
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $this->word_dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }

    /**
     * Alias to convertToWord.
     *
     * @param int $number
     *
     * @return string
     */
    public function word($number)
    {
        return $this->convertToWord($number);
    }

    public static function toRoman(int $number): string
    {
        return (new self())->roman($number);
    }

    public static function toWordOrdinal(int $number): string
    {
        //temporary fix for 20, 30, 40, 50 etc
        switch ($number) {
            case 20:
                return 'twentieth';
                break;
            case 30:
                return 'thirtieth';
                break;
            case 40:
                return 'fortieth';
                break;
            case 50:
                return 'fiftieth';
                break;
            default:
                return (new self())->wordOrdinal($number);
        }
        
    }

    public static function toNumberOrdinal(int $number): string
    {
        return (new self())->numberOrdinal($number);
    }

    public static function toWord(int $number): string
    {
        return (new self())->word($number);
    }
}
