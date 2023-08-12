<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Utility;

use Base;

/**
 * dictionary utility
 * @author Wildtier Schweiz
 */
class DictionaryUtility
{
    private array $_dictionary_data = [];

    /**
     * initialization
     * @param array $dictdata_
     */
    function __construct(array $dictionary_data_ = [])
    {
        $this->_dictionary_data = $dictionary_data_;
    }

    /**
     * get the parsed dictionary as one dimensional key value pairs
     * @return array
     */
    public function getDictionary(): array
    {
        return $this->_dictionary_data;
    }

    /**
     * get entry from current dictionary
     * @param $key_
     * @return string|null
     */
    public function getEntry(string $key_): string|NULL
    {
        return $this->_dictionary_data[$key_] ?? NULL;
    }

    /**
     * create or edit entry of current dictionary
     * @param string $key_
     * @param string $value_
     * @param bool $write_to_file_
     * @return void
     */
    public function setEntry(string $key_, string $value_ = ''): void
    {
        $this->_dictionary_data[$key_] = $value_;
    }

    /**
     * remove entry from current dictionary
     * @param string $key_
     * @param bool $write_to_file_
     * @return void
     */
    public function removeEntry(string $key_): void
    {
        unset($this->_dictionary_data[$key_]);
    }
}
