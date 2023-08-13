<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Service;

use WildtierSchweiz\F3App\Iface\ServiceInterface;
use Prefab;
use Base;

/**
 * app config loader and defaults
 */
final class ConfigService extends Prefab implements ServiceInterface
{
    private const DEFAULT_OPTIONS = [
        'path' => '../config/',
        'allow' => false
    ];
    private static Base $_f3;
    private static array $_options = [];

    private static string $_default_configs = __DIR__ . '/../Config/';
    private static string $_default_dictionaries = __DIR__ . '/../../dict/';

    /**
     * constructor
     * - load config defaults
     * - overwrite config defaults with actual config
     */
    function __construct(array $options_)
    {
        self::$_f3 = Base::instance();
        self::$_options = array_merge(self::DEFAULT_OPTIONS, $options_);
        self::$_f3->config(__DIR__ . '/../Config/default.ini');

        $_default_configs = glob(self::$_default_configs . '*.ini');
        if ($_default_configs !== false)
            foreach ($_default_configs as $file_)
                self::$_f3->config($file_, (bool)self::$_options['allow']);

        $_configs = glob(self::$_options['path'] . '*.ini');
        if ($_configs !== false)
            foreach ($_configs as $file_)
                self::$_f3->config($file_, (bool)self::$_options['allow']);

        $_default_dictionaries = glob(self::$_default_dictionaries . '*.ini');
        if ($_default_dictionaries !== false)
            foreach ($_default_dictionaries as $file_)
                self::$_f3->config($file_);
    }

    /**
     * get service options
     * @return array
     */
    public static function getOptions(): array
    {
        return self::$_options;
    }
}
