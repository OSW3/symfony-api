<?php 
namespace OSW3\Api\Enum\Pagination;

use OSW3\Api\Trait\EnumTrait;

enum Headers: string 
{
    use EnumTrait;
    
    case TOTAL_COUNT     = 'X-Total-Count';
    case TOTAL_PAGES     = 'X-Total-Pages';
    case PER_PAGE        = 'X-Per-Page';
    case CURRENT_PAGE    = 'X-Current-Page';
    case NEXT_PAGE       = 'X-Next-Page';
    case PREVIOUS_PAGE   = 'X-Previous-Page';
    case SELF_PAGE       = 'X-Self-Page';
    case FIRST_PAGE      = 'X-First-Page';
    case LAST_PAGE       = 'X-Last-Page';
} 