<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case Processing = 'processing';
    case ManualRequired = 'manual_required';
    case Refunded = 'refunded';
    case Rejected = 'rejected';
    case Failed = 'failed';
}
