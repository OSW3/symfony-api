<?php 
namespace OSW3\Api\Enum;

use OSW3\Api\Trait\EnumTrait;

enum MimeType: string 
{
    use EnumTrait;
    
    case JSON = 'application/json';
    case XML  = 'application/xml';
    case YAML = 'application/x-yaml';
    case CSV  = 'text/csv';
    case TOON = 'application/x-toon';
    case TOML = 'application/toml';
} 