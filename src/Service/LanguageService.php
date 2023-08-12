<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Service;

use Base;
use Prefab;
use WildtierSchweiz\F3App\Iface\ServiceInterface;
use WildtierSchweiz\F3App\Utility\DictionaryUtility;
use WildtierSchweiz\F3App\Utility\FilesystemUtility;

/**
 * language service for f3 framework
 * @author Wildtier Schweiz
 */
final class LanguageService extends Prefab implements ServiceInterface
{
    private const FILE_LINE_BREAK = "\r\n";
    private const DEFAULT_OPTIONS = [
        'dictionaryfilefilter' => '/(?i:^.*\.(ini)$)/m',
        'sourcefilefilter' => '/(?i:^.*\.(php|htm|html)$)/m',
    ];

    private static Base $_f3;
    private static FilesystemUtility $_filesystem;
    private static DictionaryUtility $_service;
    private static array $_options = [];
    private static array $_dictionary_parsed = [];

    /**
     * constructor
     * @param array $options_
     */
    function __construct(array $options_)
    {
        self::$_f3 = Base::instance();
        self::$_filesystem = FilesystemUtility::instance();
        self::$_options = array_merge(
            self::DEFAULT_OPTIONS,
            [
                'dictionarypath' => self::$_f3->get('LOCALES'),
                'dictionaryprefix' => self::$_f3->get('PREFIX'),
                'sourcepaths' => [
                    self::$_f3->get('UI'),
                    self::$_f3->get('application.sourcedir'),
                ],
            ],
            $options_
        );
        $_current_language = self::getCurrentLanguage(true);
        self::$_dictionary_parsed = self::parseDictionary(self::getDictionaryData($_current_language));
        self::$_service = new DictionaryUtility(self::$_dictionary_parsed);
    }

    /**
     * load dictionary data of a language
     * @param string $language_
     * @return void
     */
    public static function loadDictionaryData(string $language_ = ''): void
    {
        $_language = self::getCurrentLanguage(true);
        self::$_dictionary_parsed = self::parseDictionary(self::getDictionaryData($_language));
        self::$_service = new DictionaryUtility(self::$_dictionary_parsed);
        return;
    }

    /**
     * parse a dictionary key to parts
     * @param string $key_
     */
    public static function parseKey(string $key_): array
    {
        $_t = explode('.', (string)$key_, 3);
        $_parts_count = count($_t);
        $_has_prefix = self::$_options['dictionaryprefix'] === $_t[0];
        $_has_section = ($_has_prefix && $_parts_count > 2) || (!$_has_prefix && $_parts_count === 2);
        $_result = [
            'prefix' => $_has_prefix ? $_t[0] : '',
            'section' => $_t[$_has_prefix ? 1 : 0],
            'key' => $_t[$_has_prefix && $_has_section ? 2 : ($_has_section ? 1 : 0)],
        ];
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
    public static function writeDictionaryFile(?string $filename_ = NULL, ?array $dictionary_parsed_ = NULL, bool $quote_strings_ = false): int|false
    {
        $_result = [];
        $_filename = $filename_ ?? $this->_filename;
        $_dictionary_parsed = $dictionary_parsed_ ?? self::$_dictionary_parsed;
        $_section = '';
        foreach ($_dictionary_parsed as $k_ => $v_) {
            $_t = self::parseKey((string)$k_);
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
     * get dictionary data for a language
     * also contains fallback values, not present in the language requested (f3 standard)
     * @param string $language_
     * @return array
     */
    private static function getDictionaryData(string $language_ = ''): array
    {
        $_language = $language_ ?: self::getCurrentLanguage(true);
        $_t = self::$_f3->get('LANGUAGE');
        self::$_f3->set('LANGUAGE', $_language);
        $_result = self::$_f3->get(self::$_options['dictionaryprefix']);
        self::$_f3->set('LANGUAGE', $_t);
        return $_result;
    }

    /**
     * delete the entire dictionary
     * @return bool
     */
    public static function removeDictionary(string $language_ = ''): bool
    {
        $_language = $language_ ?: self::getCurrentLanguage(false);
        $_filename = self::$_options['dictionarypath'] . $_language . '.ini';
        return unlink($_filename);
    }

    /**
     * get service options
     * @return array
     */
    public static function getOptions(): array
    {
        return self::$_options;
    }

    /**
     * get service instance
     * @return DictionaryUtility|null
     */
    public static function getService(): DictionaryUtility|NULL
    {
        return self::$_service;
    }

    /**
     * get available languages 
     * based on existing language dictionary files
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        $_result = [];
        $_filenames = self::$_filesystem::recursiveDirectorySearch(self::$_options['dictionarypath'], self::$_options['dictionaryfilefilter']);
        foreach ($_filenames as $file_) {
            $_t = explode('.', array_pop(explode(DIRECTORY_SEPARATOR, $file_)))[0];
            $_result[] = $_t;
        }
        return $_result;
    }

    /**
     * check if a language is available 
     * based on existing language dictionary files
     * @param string $language_
     * @return bool
     */
    public static function isAvailableLanguage(string $language_): bool
    {
        return in_array($language_, self::getAvailableLanguages());
    }

    /**
     * get the current framework language code
     * @param $fallback_on_unavailable_
     * @return string 
     */
    public static function getCurrentLanguage(bool $fallback_on_unavailable_ = false): string
    {
        $_language = (explode(',', self::$_f3->get('LANGUAGE'))[0] ?? '');
        if ($fallback_on_unavailable_ === true && !in_array($_language, self::getAvailableLanguages()))
            $_language = (explode(',', self::$_f3->get('FALLBACK'))[0] ?? '');
        return $_language;
    }

    /**
     * check if a dictionary var is used in backend of frontend code
     * @param string $key_
     * @param string $filename_filter_
     * @param array &$filenames_ (optional) retrieve filenames containing the keys
     * @return int
     */
    public static function checkDictionaryUsage(string $key_ = '', array &$filenames_ = NULL): int
    {
        $_i = 0;
        $_files_names = [];
        foreach (self::$_options['sourcepaths'] ?? [] as $dir_)
            $_files_names = array_merge($_files_names, self::$_filesystem::recursiveDirectorySearch($dir_, self::$_options['sourcefilefilter']));
        $_t = '(' . ($key_ !== '' ? $key_ : implode('|', array_keys(self::$_dictionary_parsed))) . ')';
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
     * parse dictionary to single level
     * @param array $dictionary_node_
     * @return array
     */
    private static function parseDictionary(array $dictionary_node_ = []): array
    {
        static $_result = [];
        static $_stack_keys = [];
        // add the prefix to the keys, one time
        if (!in_array(self::$_options['dictionaryprefix'], $_stack_keys))
            $_stack_keys[] = self::$_options['dictionaryprefix'];
        // add dictionary node to the result
        foreach ($dictionary_node_ as $k_ => $v_) {
            // add new key to stack
            $_stack_keys[] = $k_;
            // if child nodes present, reparse
            if (is_array($v_))
                self::parseDictionary($v_);
            // when no further childs, store value to result with generated key
            else $_result[implode('.', $_stack_keys)] = $v_;
            // cleanup key
            while (($_t = array_pop($_stack_keys)) && $_t !== $k_);
        }
        return $_result;
    }
}
