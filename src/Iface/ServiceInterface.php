<?php

declare(strict_types=1);

namespace WildtierSchweiz\F3App\Iface;

interface ServiceInterface
{
    function __construct(array $options_);
    static function getOptions(): array;
}
