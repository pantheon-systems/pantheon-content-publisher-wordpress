<?php

namespace PccPhpSdk\api\Query\Enums;

/**
 * Supported Publish Status.
 */
enum PublishStatus: string
{
    case PUBLISHED = 'published';
    case UNPUBLISHED = 'unpublished';
    case DRAFT = 'draft';
}
