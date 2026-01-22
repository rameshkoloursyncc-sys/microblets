<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DieConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'belt_type',
        'section',
        'stock_per_die',
        'notes',
        'is_active'
    ];

    protected $casts = [
        'stock_per_die' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get stock per die for a specific belt type and section
     */
    public static function getStockPerDie(string $beltType, string $section): float
    {
        $config = self::where('belt_type', $beltType)
            ->where('section', $section)
            ->where('is_active', true)
            ->first();

        return $config ? $config->stock_per_die : 30.0; // Default 30 if not configured
    }

    /**
     * Set or update die configuration
     */
    public static function setDieConfiguration(string $beltType, string $section, float $stockPerDie, ?string $notes = null): self
    {
        return self::updateOrCreate(
            ['belt_type' => $beltType, 'section' => $section],
            [
                'stock_per_die' => $stockPerDie,
                'notes' => $notes,
                'is_active' => true
            ]
        );
    }

    /**
     * Get all configurations grouped by belt type
     */
    public static function getAllGrouped(): array
    {
        return self::where('is_active', true)
            ->orderBy('belt_type')
            ->orderBy('section')
            ->get()
            ->groupBy('belt_type')
            ->toArray();
    }

    /**
     * Seed default configurations
     */
    public static function seedDefaults(): void
    {
        $defaults = [
            'vee' => [
                'A' => 34, 'B' => 26, 'C' => 20, 'D' => 35, 'E' => 30,
                'SPA' => 20, 'SPB' => 24, 'SPC' => 20, 'SPZ' => 28,
                '3V' => 25, '5V' => 20, '8V' => 15
            ],
            'cogged' => [
                'AX' => 45, 'BX' => 40, 'CX' => 35,
                'XPA' => 50, 'XPB' => 45, 'XPC' => 40, 'XPZ' => 55,
                '3VX' => 25, '5VX' => 20
            ],
            'poly' => [
                'PJ' => 100, 'PK' => 80, 'PL' => 70, 'PM' => 60, 'PH' => 50,
                'DPL' => 65, 'DPK' => 75
            ],
            'tpu' => [
                '5M' => 30, '8M' => 25, '8M RPP' => 25, 'S8M' => 20, '14M' => 15,
                'XL' => 35, 'L' => 40, 'H' => 35, 'AT5' => 50, 'AT10' => 30, 'T10' => 25, 'AT20' => 20
            ],
            'timing' => [
                'XL' => 30, 'L' => 35, 'H' => 30, 'XH' => 25, 'T5' => 45, 'T10' => 35,
                '3M' => 40, '5M' => 35, '8M' => 30, '14M' => 20,
                'DL' => 25, 'DH' => 20, 'D5M' => 30, 'D8M' => 25
            ],
            'special' => [
                'Conical C' => 20, 'Harvester' => 15, 'RAX' => 35, 'RBX' => 30,
                'R3VX' => 25, 'R5VX' => 20, '8M PK' => 30, '8M PL' => 25
            ]
        ];

        foreach ($defaults as $beltType => $sections) {
            foreach ($sections as $section => $stockPerDie) {
                self::setDieConfiguration($beltType, $section, $stockPerDie, 'Default configuration');
            }
        }
    }
}
