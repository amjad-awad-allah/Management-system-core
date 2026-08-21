<?php

namespace App\Core\Enums;

enum GermanBundesland: string
{
    case BW = 'BW'; // Baden-Württemberg
    case BY = 'BY'; // Bayern
    case BE = 'BE'; // Berlin
    case BB = 'BB'; // Brandenburg
    case HB = 'HB'; // Bremen
    case HH = 'HH'; // Hamburg
    case HE = 'HE'; // Hessen
    case MV = 'MV'; // Mecklenburg-Vorpommern
    case NI = 'NI'; // Niedersachsen
    case NW = 'NW'; // Nordrhein-Westfalen
    case RP = 'RP'; // Rheinland-Pfalz
    case SL = 'SL'; // Saarland
    case SN = 'SN'; // Sachsen
    case ST = 'ST'; // Sachsen-Anhalt
    case SH = 'SH'; // Schleswig-Holstein
    case TH = 'TH'; // Thüringen

    /**
     * Get all codes.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human-readable German name for a code.
     */
    public static function getName(string $code): string
    {
        return match (strtoupper($code)) {
            'BW' => 'Baden-Württemberg',
            'BY' => 'Bayern',
            'BE' => 'Berlin',
            'BB' => 'Brandenburg',
            'HB' => 'Bremen',
            'HH' => 'Hamburg',
            'HE' => 'Hessen',
            'MV' => 'Mecklenburg-Vorpommern',
            'NI' => 'Niedersachsen',
            'NW' => 'Nordrhein-Westfalen',
            'RP' => 'Rheinland-Pfalz',
            'SL' => 'Saarland',
            'SN' => 'Sachsen',
            'ST' => 'Sachsen-Anhalt',
            'SH' => 'Schleswig-Holstein',
            'TH' => 'Thüringen',
            default => 'Nordrhein-Westfalen',
        };
    }

    /**
     * Get array of all states with code and name.
     *
     * @return array<int, array{code: string, name: string}>
     */
    public static function toArray(): array
    {
        return array_map(fn (self $case) => [
            'code' => $case->value,
            'name' => self::getName($case->value),
        ], self::cases());
    }
}
