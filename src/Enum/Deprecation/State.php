<?php 
namespace OSW3\Api\Enum\Deprecation;

use OSW3\Api\Trait\EnumTrait;

enum State: string 
{
    use EnumTrait;
    
    case ACTIVE     = 'active';
    case DEPRECATED = 'deprecated';
    case REMOVED    = 'removed';
} 