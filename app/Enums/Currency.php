<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';
    case CAD = 'CAD';
    case AUD = 'AUD';
    case JPY = 'JPY';
    case CHF = 'CHF';
    case CNY = 'CNY';
    case INR = 'INR';
    case MXN = 'MXN';
    case BRL = 'BRL';

    public function getSymbol(): string
    {
        return match($this) {
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
            self::CAD => 'C$',
            self::AUD => 'A$',
            self::JPY => '¥',
            self::CHF => 'CHF',
            self::CNY => '¥',
            self::INR => '₹',
            self::MXN => '$',
            self::BRL => 'R$',
        };
    }

    public function getFormatted(): string
    {
        return match($this) {
            self::USD => 'USD ($)',
            self::EUR => 'EUR (€)',
            self::GBP => 'GBP (£)',
            self::CAD => 'CAD (C$)',
            self::AUD => 'AUD (A$)',
            self::JPY => 'JPY (¥)',
            self::CHF => 'CHF',
            self::CNY => 'CNY (¥)',
            self::INR => 'INR (₹)',
            self::MXN => 'MXN ($)',
            self::BRL => 'BRL (R$)',
        };
    }

    public function getName(): string
    {
        return match($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::GBP => 'British Pound',
            self::CAD => 'Canadian Dollar',
            self::AUD => 'Australian Dollar',
            self::JPY => 'Japanese Yen',
            self::CHF => 'Swiss Franc',
            self::CNY => 'Chinese Yuan',
            self::INR => 'Indian Rupee',
            self::MXN => 'Mexican Peso',
            self::BRL => 'Brazilian Real',
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
                'label' => $case->getFormatted(),
                'symbol' => $case->getSymbol(),
            ],
            self::cases()
        );
    }
}
