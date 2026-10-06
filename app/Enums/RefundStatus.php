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

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Menunggu keputusan',
            self::Approved => 'Disetujui',
            self::Processing => 'Diproses Midtrans',
            self::ManualRequired => 'Perlu diproses manual',
            self::Refunded => 'Dana dikembalikan',
            self::Rejected => 'Ditolak',
            self::Failed => 'Proses gagal',
        };
    }
}
