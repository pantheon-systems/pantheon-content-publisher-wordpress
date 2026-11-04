<?php

namespace PccPhpSdk\api\Query\Enums;

/**
 * Supported Content Types.
 */
enum ContentType: string
{
  case TEXT_MARKDOWN = 'TEXT_MARKDOWN';
  case TREE_PANTHEON = 'TREE_PANTHEON';
  case TREE_PANTHEON_V2 = 'TREE_PANTHEON_V2';
}
