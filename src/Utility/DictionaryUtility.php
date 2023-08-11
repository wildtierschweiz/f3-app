<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Utility;

use Base;

/**
 * language dictionary utility
 * @author Wildtier Schweiz
 */
class DictionaryUtility
{
    private const FILE_LINE_BREAK = "\r\n";

    private Base $_f3;
    private FilesystemUtility $_fs;
    private string $_language = '';
    private string $_filename = '';
    private string $_prefix = '';
    private array $_dictionary_parsed = [];

    private string $_path_dictionaries = '';
    private string $_path_views = '';
    private string $_path_source = '';

    /**
     * initialization
     * @param ?string $language_
     */
    function __construct(?string $language_ = NULL)
    {
        $this->_f3 = Base::instance();
        $this->_fs = FilesystemUtility::instance();

        // paths and file names related
        $this->_path_dictionaries = $this->_f3->get('LOCALES');
        $this->_path_views = $this->_f3->get('UI');
        $this->_path_source = $this->_f3->get('application.sourcedir');
        $this->_language = $language_ ?? (explode(',', $this->_f3->get('LANGUAGE'))[0] ?? '');
        $this->_filename = $this->detectFilename(true);

        // key related
        $this->_prefix = str_replace('.', '', $this->_f3->get('PREFIX'));

        // temporary switch framework language, to load correct dictionary
        $_t = $this->_f3->get('LANGUAGE');
        $this->_f3->set('LANGUAGE', $this->_language);
        $_dictionary_vars = $this->_f3->get($this->_prefix) ?? [];
        $this->_dictionary_parsed = $this->parseDictionary($_dictionary_vars);
        $this->_f3->set('LANGUAGE', $_t);
    }

    /**
     * parse dictionary to single level
     * @param array $dictionary_node_
     * @return array
     */
    private function parseDictionary(array $dictionary_node_ = []): array
    {
        static $_result = [];
        static $_stack_keys = [];
        // add the prefix to the keys, one time
        if (!in_array($this->_prefix, $_stack_keys))
            $_stack_keys[] = $this->_prefix;
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
     * @param ?string $file_name_ path and file name to write to
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
        foreach ($_dictionary_parsed as $k_ => $v_) {
            $_t = $this->parseKey((string)$k_);
            // if first section or next section
            if ($_section === '' || $_t['section'] !== $_section) {
                // if previous section exists
                if ($_section !== '')
                    $_result[] = '';
                $_section = $_t['section'];
                $_result[] = '[' . $_section . ']';
            }
            $_key = $_t['key'];
            $_result[] = $_key . ' = ' . ($quote_strings_ === true ? (is_numeric($v_) ? $v_ : '"' . $v_ . '"') : $v_);
        }
        return file_put_contents($_filename, implode(self::FILE_LINE_BREAK, $_result));
    }

    /**
     * parse a dictionary key to parts
     * @param string $key_
     */
    public function parseKey(string $key_): array
    {
        $_t = explode('.', (string)$key_, 3);
        $_parts_count = count($_t);
        $_has_prefix = $this->_prefix === $_t[0];
        $_has_section = ($_has_prefix && $_parts_count > 2) || (!$_has_prefix && $_parts_count === 2);
        $_result = [
            'prefix' => $_has_prefix ? $_t[0] : '',
            'section' => $_t[$_has_prefix ? 1 : 0],
            'key' => $_t[$_has_prefix && $_has_section ? 2 : ($_has_section ? 1 : 0)],
        ];
        return $_result;
    }

    /**
     * check if a dictionary var is used in backend of frontend code
     * @param string $key_
     * @param string $filename_filter_
     * @param array &$filenames_ (optional) retrieve filenames containing the keys
     * @return int
     */
    public function checkDictionaryUsage(string $key_ = '', string $filename_filter_ = '/(?i:^.*\.(php|htm|html)$)/m', array &$filenames_ = NULL): int
    {
        $_i = 0;
        $_files_directories = [
            $this->_path_source,
            $this->_path_views,
        ];
        $_files_names = [];
        foreach ($_files_directories as $dir_)
            $_files_names = array_merge($_files_names, $this->_fs::recursiveDirectorySearch($dir_, $filename_filter_));
        $_t = '(' . ($key_ !== '' ? $key_ : implode('|', array_keys($this->_dictionary_parsed))) . ')';
        foreach ($_files_names as $file_) {
            $_contents = file_get_contents($file_);
            if (preg_match($_t, $_contents) === 1) {
                if ($filenames_ !== NULL)
                    $filenames_[] = $file_;
                $_i++;
            }
        }
        return $_i;
    }

    /**
     * detect the filename of the dictionary and optionally create dictionary, if not exists for 
     * the chosen language
     * @param bool $create_if_not_exists_
     * @return string
     */
    private function detectFilename(bool $create_if_not_exists_ = false): string
    {
        $_filename = $this->_path_dictionaries . $this->_language . '.ini';
        if (!is_file($_filename) && $create_if_not_exists_ === true)
            file_put_contents($_filename, '');
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
     * get entry from current dictionary
     * @param $key_
     * @return string|null
     */
    public function getEntry(string $key_): string|NULL
    {
        return $this->_dictionary_parsed[$key_] ?? NULL;
    }

    /**
     * create or edit entry of current dictionary
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
     * remove entry from current dictionary
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

    /**
     * delete the entire dictionary
     * @return bool
     */
    public function removeDictionary(): bool
    {
        $_filename = $this->detectFilename();
        return unlink($_filename);
    }
}
