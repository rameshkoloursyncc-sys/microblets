#!/bin/bash

# Vee Belts Production Seeder Script
# This script seeds all vee belt data to your production database

echo "========================================="
echo "Vee Belts Production Seeder"
echo "========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from your Laravel root directory."
    exit 1
fi

# Confirm before proceeding
read -p "⚠️  This will seed vee belt data to your database. Continue? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "📦 Running migrations..."
php artisan migrate --force

echo ""
echo "🌱 Seeding rate formulas..."
php artisan db:seed --class=RateFormulaSeeder --force

echo ""
echo "🌱 Seeding vee belt products..."
php artisan db:seed --class=VeeBeltSeeder --force

echo ""
echo "✅ Seeding complete!"
echo ""
echo "📊 Database Summary:"
php artisan tinker --execute="
echo 'Total Vee Belts: ' . App\Models\VeeBelt::count() . PHP_EOL;
echo 'A Section: ' . App\Models\VeeBelt::where('section', 'A')->count() . PHP_EOL;
echo 'B Section: ' . App\Models\VeeBelt::where('section', 'B')->count() . PHP_EOL;
echo 'C Section: ' . App\Models\VeeBelt::where('section', 'C')->count() . PHP_EOL;
echo 'SPA Section: ' . App\Models\VeeBelt::where('section', 'SPA')->count() . PHP_EOL;
echo 'Rate Formulas: ' . App\Models\RateFormula::count() . PHP_EOL;
"

echo ""
echo "========================================="
echo "Done! Your vee belts are ready."
echo "========================================="
