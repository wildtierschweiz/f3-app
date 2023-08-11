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
    private const DEFAULT_OPTIONS = [
        'dictionaryfilefilter' => '/(?i:^.*\.(ini)$)/m',
    ];

    private static Base $_f3;
    private static FilesystemUtility $_filesystem;
    private static DictionaryUtility $_service;
    private static array $_options = [];

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
            ],
            $options_
        );
        $_current_language = self::getCurrentLanguage(true);
        $_dictionary_data = self::getDictionaryData($_current_language);
        self::$_service = new DictionaryUtility($_current_language, $_dictionary_data);
    }

    /**
     * get dictionary data for a language
     * also contains fallback values, not present in the language requested (f3 standard)
     * @param string $language_
     * @return array
     */
    public static function getDictionaryData(string $language_ = ''): array
    {
        $_language = $language_ ?: self::getCurrentLanguage(true);
        $_t = self::$_f3->get('LANGUAGE');
        self::$_f3->set('LANGUAGE', $_language);
        $_result = self::$_f3->get(self::$_options['dictionaryprefix']);
        self::$_f3->set('LANGUAGE', $_t);
        return $_result;
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
}
