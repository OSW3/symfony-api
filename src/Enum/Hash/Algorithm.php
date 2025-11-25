<?php 
namespace OSW3\Api\Enum\Hash;

use OSW3\Api\Trait\EnumTrait;

enum Algorithm: string 
{
    use EnumTrait;
    
    case MD5 = 'md5';
    case SHA1 = 'sha1';
    case SHA256 = 'sha256';
    case SHA512 = 'sha512';
} 