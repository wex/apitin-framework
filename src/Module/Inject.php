<?php declare(strict_types = 1);

namespace Apitin\Module;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
    public function __construct(string $className)
    {        
    }
}