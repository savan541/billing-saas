<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match($this) {
            self::DRAFT => 'Draft',
            self::SENT => 'Sent',
            self::PAID => 'Paid',
            self::OVERDUE => 'Overdue',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::PAID => 'green',
            self::OVERDUE => 'red',
            self::CANCELLED => 'gray',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::DRAFT => 'document-text',
            self::SENT => 'paper-airplane',
            self::PAID => 'check-circle',
            self::OVERDUE => 'exclamation-triangle',
            self::CANCELLED => 'x-circle',
        };
    }

    public function isEditable(): bool
    {
        return match($this) {
            self::DRAFT => true,
            self::SENT => false,
            self::PAID => false,
            self::OVERDUE => false,
            self::CANCELLED => false,
        };
    }

    public function canBeSent(): bool
    {
        return match($this) {
            self::DRAFT => true,
            self::SENT => false,
            self::PAID => false,
            self::OVERDUE => false,
            self::CANCELLED => false,
        };
    }

    public function canBePaid(): bool
    {
        return match($this) {
            self::DRAFT => false,
            self::SENT => true,
            self::PAID => false,
            self::OVERDUE => true,
            self::CANCELLED => false,
        };
    }

    public function canBeCancelled(): bool
    {
        return match($this) {
            self::DRAFT => true,
            self::SENT => true,
            self::PAID => false,
            self::OVERDUE => true,
            self::CANCELLED => false,
        };
    }

    public function canBeMarkedAsPaid(): bool
    {
        return match($this) {
            self::DRAFT => false,
            self::SENT => true,
            self::PAID => false,
            self::OVERDUE => true,
            self::CANCELLED => false,
        };
    }

    public static function getAll(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }

    public static function getOptions(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'color' => $case->getColor(),
                'icon' => $case->getIcon(),
            ],
            self::cases()
        );
    }
}
