<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Utility;

use Base;

/**
 * language dictionary utility
 */
class DictionaryUtility
{
    private const FILE_LINE_BREAK = "\r\n";

    private Base $_f3;
    private FilesystemUtility $_fs;
    private array $_dictionary_parsed = [];
    private string $_filename = '';
    private string $_language = '';

    /**
     * initialization
     */
    function __construct(?string $language_ = NULL)
    {
        $this->_f3 = Base::instance();
        $this->_fs = FilesystemUtility::instance();
        $this->_language = $language_ ?? (explode(',', $this->_f3->get('LANGUAGE'))[0] ?? '');
        $this->_filename = $this->detectFilename();
        $this->_dictionary_parsed = $this->parseDictionary($this->_f3->get($this->_f3->get('PREFIX')));
    }

    /**
     * parse dictionary to single level
     * @param array $dictionary_node_ = []
     * @return array
     */
    private function parseDictionary(array $dictionary_node_ = []): array
    {
        $_dict_var_prefix = str_replace('.', '', $this->_f3->get('PREFIX'));
        static $_result = [];
        static $_stack_keys = [];
        // add the prefix to the keys, one time
        if (!in_array($_dict_var_prefix, $_stack_keys))
            $_stack_keys[] = $_dict_var_prefix;
        // add dictionary node to the result
        foreach ($dictionary_node_ as $k_ => $v_) {
            // add new key to stack
            $_stack_keys[] = $k_;
            // if child nodes present, reparse
            if (is_array($v_))
                $this->parseDictionary($v_);
            // when no further childs, store value to result with generated key
            else $_result[implode('.', $_stack_keys)] = $v_;
            // cleanup key
            while (($_t = array_pop($_stack_keys)) && $_t !== $k_);
        }
        return $_result;
    }

    /**
     * write data array to ini file
     * @author https://stackoverflow.com/questions/5695145/how-to-read-and-write-to-an-ini-file-with-php
     * @param ?string $file_name_ ini file to write to
     * @param ?array $dictionary_parsed_ flat dimensional key value pairs of the dictionary
     * @param bool $quote_strings_
     * @return int|false
     */
    public function writeDictionaryIniFile(?string $filename_ = NULL, ?array $dictionary_parsed_ = NULL, bool $quote_strings_ = false): int|false
    {
        $_result = [];
        $_filename = ($filename_ !== NULL ? $filename_ : $this->_filename);
        $_dictionary_parsed = ($dictionary_parsed_ !== NULL ? $dictionary_parsed_ : $this->_dictionary_parsed);
        $_section = '';
        $_dict_var_prefix = $this->_f3->get('PREFIX');
        foreach ($_dictionary_parsed as $k_ => $v_) {
            $_t = explode('.', (string)$k_);
            // remove prefix, if present
            if ($_t[0] === str_replace('.', '', $_dict_var_prefix))
                array_shift($_t);
            // if first section or next section
            if ($_section === '' || $_t[0] !== $_section) {
                // if previous section exists
                if ($_section !== '')
                    $_result[] = '';
                $_section = $_t[0];
                $_result[] = '[' . $_section . ']';
            }
            // remove section from key name
            array_shift($_t);
            // key name without section and prefix
            $_key = implode('.', $_t);
            $_result[] = $_key . ' = ' . ($quote_strings_ === true ? (is_numeric($v_) ? $v_ : '"' . $v_ . '"') : $v_);
        }
        return file_put_contents($_filename, implode(self::FILE_LINE_BREAK, $_result));
    }

    /**
     * check if a dictionary var is used in backend of frontend code
     * @param string $key_
     * @return int
     */
    public function checkDictionaryUsage(string $key_ = ''): int
    {
        $_i = 0;
        $_files_filter = '/(?i:^.*\.(php|htm|html)$)/m';
        $_files_directories = [
            // src directory
            $this->_f3->get('application.sourcedir'),
            // view templates directory
            $this->_f3->get('UI'),
        ];
        $_files_names = [];
        foreach ($_files_directories as $dir_)
            $_files_names = array_merge($_files_names, $this->_fs::recursiveDirectorySearch($dir_, $_files_filter));
        $_t = '(' . ($key_ !== '' ? $key_ : implode('|', array_keys($this->_dictionary_parsed))) . ')';
        foreach ($_files_names as $file_) {
            $_contents = file_get_contents($file_);
            if (preg_match($_t, $_contents) === 1)
                //if (str_contains($_contents, $hive_key_))
                $_i++;
        }
        return $_i;
    }

    /**
     * detect the filename of the dictionary
     * @return string
     */
    private function detectFilename(): string
    {
        $_filename = $this->_f3->get('LOCALES') . $this->_language . '.ini';
        return is_file($_filename) ? $_filename : '';
    }

    /**
     * get the parsed dictionary as one dimensional key value pairs
     * @return array
     */
    public function getDictionaryParsed(): array
    {
        return $this->_dictionary_parsed;
    }

    /**
     * get dictionary entry
     * @param $key_
     * @return string|null
     */
    public function getEntry(string $key_): string|NULL
    {
        return $this->_dictionary_parsed[$key_] ?? NULL;
    }

    /**
     * create or edit dictionary entry
     * @param string $key_
     * @param string $value_
     * @param bool $write_to_file_
     * @return void
     */
    public function setEntry(string $key_, string $value_ = '', bool $write_to_file_ = false): void
    {
        $this->_dictionary_parsed[$key_] = $value_;
        if ($write_to_file_ === true)
            $this->writeDictionaryIniFile();
    }

    /**
     * create or edit dictionary entry
     * @param string $key_
     * @param bool $write_to_file_
     * @return void
     */
    public function removeEntry(string $key_, bool $write_to_file_ = false): void
    {
        unset($this->_dictionary_parsed[$key_]);
        if ($write_to_file_ === true)
            $this->writeDictionaryIniFile();
    }
}
