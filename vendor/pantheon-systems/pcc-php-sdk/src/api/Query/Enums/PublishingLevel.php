<?php

namespace PccPhpSdk\api\Query\Enums;

enum PublishingLevel: string
{
    case PRODUCTION = 'PRODUCTION';
    case REALTIME = 'REALTIME';
    case DRAFT = 'DRAFT';
}
