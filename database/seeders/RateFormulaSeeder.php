<?php

namespace Database\Seeders;

use App\Models\RateFormula;
use Illuminate\Database\Seeder;

class RateFormulaSeeder extends Seeder
{
    public function run(): void
    {
        $formulas = [
            // Classical Vee Belts
            [
                'category' => 'vee_belts',
                'section' => 'A',
                'formula' => 'size/1*1.05',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'B',
                'formula' => 'size/1*1.15',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'C',
                'formula' => 'size/1*1.25',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'D',
                'formula' => 'size/1*1.35',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'E',
                'formula' => 'size/1*1.45',
                'is_active' => true,
            ],
            
            // Wedge Vee Belts
            [
                'category' => 'vee_belts',
                'section' => 'SPA',
                'formula' => 'size/1*1.10',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'SPB',
                'formula' => 'size/1*1.20',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'SPC',
                'formula' => 'size/1*1.30',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => 'SPZ',
                'formula' => 'size/1*1.00',
                'is_active' => true,
            ],
            
            // Narrow Vee Belts
            [
                'category' => 'vee_belts',
                'section' => '3V',
                'formula' => 'size/10*1.50',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => '5V',
                'formula' => 'size/10*1.87',
                'is_active' => true,
            ],
            [
                'category' => 'vee_belts',
                'section' => '8V',
                'formula' => 'size/10*2.50',
                'is_active' => true,
            ],
            
            // Cogged Belts - Classical
            [
                'category' => 'cogged_belts',
                'section' => 'AX',
                'formula' => 'size/1*1.95',
                'is_active' => true,
            ],
            [
                'category' => 'cogged_belts',
                'section' => 'BX',
                'formula' => 'size/1*3.45',
                'is_active' => true,
            ],
            [
                'category' => 'cogged_belts',
                'section' => 'CX',
                'formula' => 'size/1*5.68',
                'is_active' => true,
            ],
            
            // Cogged Belts - Wedge
            [
                'category' => 'cogged_belts',
                'section' => 'XPA',
                'formula' => 'size/1*1.85',
                'is_active' => true,
            ],
            [
                'category' => 'cogged_belts',
                'section' => 'XPB',
                'formula' => 'size/1*2.95',
                'is_active' => true,
            ],
            [
                'category' => 'cogged_belts',
                'section' => 'XPC',
                'formula' => 'size/1*4.25',
                'is_active' => true,
            ],
            [
                'category' => 'cogged_belts',
                'section' => 'XPZ',
                'formula' => 'size/1*1.45',
                'is_active' => true,
            ],
            
            // Cogged Belts - Narrow
            [
                'category' => 'cogged_belts',
                'section' => '3VX',
                'formula' => 'size/10*2.15',
                'is_active' => true,
            ],
            [
                'category' => 'cogged_belts',
                'section' => '5VX',
                'formula' => 'size/10*3.25',
                'is_active' => true,
            ],
            
            // Poly Belts - V-Belts (now with configurable divisor)
            [
                'category' => 'poly_belts',
                'section' => 'PJ',
                'formula' => 'ribs/25.4*0.36',
                'is_active' => true,
            ],
            [
                'category' => 'poly_belts',
                'section' => 'PK',
                'formula' => 'ribs/25.4*0.59',
                'is_active' => true,
            ],
            [
                'category' => 'poly_belts',
                'section' => 'PL',
                'formula' => 'ribs/25.4*0.85',
                'is_active' => true,
            ],
            [
                'category' => 'poly_belts',
                'section' => 'PM',
                'formula' => 'ribs/25.4*1.25',
                'is_active' => true,
            ],
            [
                'category' => 'poly_belts',
                'section' => 'PH',
                'formula' => 'ribs/25.4*1.85',
                'is_active' => true,
            ],
            
            // Poly Belts - Double Sided
            [
                'category' => 'poly_belts',
                'section' => 'DPL',
                'formula' => 'ribs/25.4*1.15',
                'is_active' => true,
            ],
            [
                'category' => 'poly_belts',
                'section' => 'DPK',
                'formula' => 'ribs/25.4*0.89',
                'is_active' => true,
            ],
            
            // TPU Belts (now with configurable divisor)
            [
                'category' => 'tpu_belts',
                'section' => '5M',
                'formula' => 'size/1*2.50',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => '8M',
                'formula' => 'size/1*3.20',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => '8M RPP',
                'formula' => 'size/1*3.50',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'S8M',
                'formula' => 'size/1*4.00',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => '14M',
                'formula' => 'size/1*5.50',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'XL',
                'formula' => 'size/1*2.80',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'L',
                'formula' => 'size/1*3.00',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'H',
                'formula' => 'size/1*3.20',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'AT5',
                'formula' => 'size/1*2.00',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'AT10',
                'formula' => 'size/1*3.80',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'T10',
                'formula' => 'size/1*4.00',
                'is_active' => true,
            ],
            [
                'category' => 'tpu_belts',
                'section' => 'AT20',
                'formula' => 'size/1*6.50',
                'is_active' => true,
            ],
        ];

        foreach ($formulas as $formula) {
            RateFormula::updateOrCreate(
                [
                    'category' => $formula['category'],
                    'section' => $formula['section'],
                ],
                $formula
            );
        }
    }
}
