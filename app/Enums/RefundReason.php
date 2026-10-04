<?php

namespace App\Enums;

enum RefundReason: string
{
    case ContentMismatch = 'content_mismatch';
    case ScheduleConflict = 'schedule_conflict';
    case TechnicalIssue = 'technical_issue';
    case AccidentalPurchase = 'accidental_purchase';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ContentMismatch => 'Materi kursus tidak sesuai kebutuhan',
            self::ScheduleConflict => 'Jadwal kursus tidak sesuai',
            self::TechnicalIssue => 'Mengalami kendala teknis',
            self::AccidentalPurchase => 'Pembelian tidak disengaja',
            self::Other => 'Lainnya',
        };
    }
}
