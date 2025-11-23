<?php 
namespace OSW3\Api\Enum\Version;

use OSW3\Api\Trait\EnumTrait;

enum Headers: string 
{
    use EnumTrait;
    
    case API_VERSION             = 'X-API-Version';
    case API_ALL_VERSIONS        = 'X-API-All-Versions';
    case API_SUPPORTED_VERSIONS  = 'X-API-Supported-Versions';
    case API_DEPRECATED_VERSIONS = 'X-API-Deprecated-Versions';
} 