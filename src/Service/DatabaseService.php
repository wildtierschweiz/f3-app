<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Service;

use Prefab;
use WildtierSchweiz\F3App\Iface\ServiceInterface;
use DB\Jig;
use DB\Mongo;
use DB\SQL;

final class DatabaseService extends Prefab implements ServiceInterface
{
    private const DEFAULT_OPTIONS = [
        'engine' => 'sql',
        'type' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'data' => '',
        'user' => '',
        'pass' => '',
        'folder' => '',
    ];
    private static $_service;
    private static array $_options = [];

    function __construct(array $options_)
    {
        self::$_options = array_merge(self::DEFAULT_OPTIONS, $options_);
        switch (strtolower(self::$_options['engine'] ?? '')) {
            default:
                self::$_service = NULL;
                break;
            case 'sql':
                self::$_service = new SQL(
                    (self::$_options['type'] ?? '')
                        . ':host=' . (self::$_options['host'] ?? '')
                        . ';port=' . (self::$_options['port'] ?? '')
                        . ';dbname=' . (self::$_options['data'] ?? ''),
                    (self::$_options['user'] ?? ''),
                    (self::$_options['pass'] ?? '')
                );
                break;
            case 'jig':
                self::$_service = new Jig(
                    (self::$_options['folder'] ?? '') . (self::$_options['data'] ?? '') . '/',
                    Jig::FORMAT_JSON
                );
                break;
            case 'mongo':
                self::$_service = new Mongo(
                    'mongodb://'
                        . (self::$_options['host'] ?? '')
                        . ':'
                        . (self::$_options['port'] ?? ''),
                    (self::$_options['data'] ?? ''),
                    NULL
                );
                break;
        }
    }

    /**
     * get service instance
     * @return SQL|Mongo|Jig|null
     */
    public static function getService()
    {
        return self::$_service;
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
