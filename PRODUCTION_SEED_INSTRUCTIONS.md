# Vee Belts Production Seeding Instructions

## Quick Start (One Command)

Run this single command to seed everything:

```bash
php artisan migrate --force && php artisan db:seed --class=RateFormulaSeeder --force && php artisan db:seed --class=VeeBeltSeeder --force
```

## Or Use the Script

```bash
./seed_vee_belts_production.sh
```

## What Gets Seeded

1. **Rate Formulas** (12 formulas)
   - A, B, C, D, E sections (multiply formulas)
   - SPA, SPB, SPC, SPZ sections (multiply formulas)
   - 3V, 5V, 8V sections (divide_multiply formulas)

2. **Vee Belt Products** (~1,091 products)
   - A Section: 137 products
   - B Section: 211 products
   - C Section: 245 products
   - D Section: 113 products
   - E Section: 11 products
   - SPA Section: 39 products
   - SPB Section: 101 products
   - SPC Section: 99 products
   - SPZ Section: 57 products
   - 3V Section: 26 products
   - 5V Section: 12 products
   - 8V Section: 16 products

## Verify After Seeding

```bash
php artisan tinker --execute="echo 'Total: ' . App\Models\VeeBelt::count() . PHP_EOL;"
```

## Notes

- The seeder uses `updateOrCreate()` so it's safe to run multiple times
- Existing products will be updated, not duplicated
- All JSON files are read from `resources/js/mock/` directory



# Option 1: One command
php artisan migrate --force && php artisan db:seed --class=RateFormulaSeeder --force && php artisan db:seed --class=VeeBeltSeeder --force

# Option 2: Use the script
./seed_vee_belts_production.sh
pkill -9 -f "php.*artisan.*serve" && sleep 1 && echo "Killed all Laravel server processes"

pkill -9 php && sleep 2 && echo "All PHP processes killed"