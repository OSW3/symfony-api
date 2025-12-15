<?php 
namespace OSW3\Api\Enum\Version;

use OSW3\Api\Trait\EnumTrait;

enum Location: string 
{
    use EnumTrait;
    
    case PATH      = 'path';
    case HEADER    = 'header';
    case QUERY     = 'query';
    // case COOKIE    = 'cookie';
    case SUBDOMAIN = 'subdomain';
} 