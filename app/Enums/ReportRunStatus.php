<?php

namespace App\Enums;

enum ReportRunStatus: string
{
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';
}
