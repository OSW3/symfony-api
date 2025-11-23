<?php 
namespace OSW3\Api\Enum\Deprecation;

use OSW3\Api\Trait\EnumTrait;

enum Headers: string 
{
    use EnumTrait;
    
    case DEPRECATION     = 'Deprecation';
    case SUNSET          = 'Sunset';
    case LINK            = 'Link';
    case MESSAGE         = 'X-Message';
} 