<?php declare(strict_types = 1);

namespace Apitin;

interface DI
{
    public static function factory(): self;
}