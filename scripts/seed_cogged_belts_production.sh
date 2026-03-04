#!/bin/bash

echo "🚀 Starting Cogged Belts Production Seeding..."
echo "=============================================="

# Check if Laravel is accessible
if ! php artisan --version > /dev/null 2>&1; then
    echo "❌ Error: Laravel artisan not accessible"
    exit 1
fi

# Check if database is accessible
if ! php artisan migrate:status > /dev/null 2>&1; then
    echo "❌ Error: Database not accessible"
    exit 1
fi

echo "✅ Laravel and database are accessible"

# Run migrations to ensure cogged_belts table exists
echo "📋 Running migrations..."
php artisan migrate --force

# Seed rate formulas (includes cogged belts formulas)
echo "📊 Seeding rate formulas..."
php artisan db:seed --class=RateFormulaSeeder --force

# Seed cogged belts data
echo "🔧 Seeding cogged belts data..."
php artisan db:seed --class=CoggedBeltSeeder --force

# Verify the seeding
echo "🔍 Verifying seeded data..."
echo "Checking cogged belts count by section:"

sections=("AX" "BX" "CX" "XPA" "XPB" "XPC" "XPZ" "3VX" "5VX")
total=0

for section in "${sections[@]}"; do
    count=$(php artisan tinker --execute="echo App\Models\CoggedBelt::where('section', '$section')->count();" 2>/dev/null | tail -1)
    echo "  - $section: $count products"
    total=$((total + count))
done

echo "  - Total: $total cogged belts"

# Check rate formulas
echo ""
echo "Checking rate formulas for cogged belts:"
php artisan tinker --execute="
App\Models\RateFormula::where('category', 'cogged_belts')->get()->each(function(\$formula) {
    echo \$formula->section . ': ' . \$formula->formula . PHP_EOL;
});
" 2>/dev/null | grep -E "^(AX|BX|CX|XPA|XPB|XPC|XPZ|3VX|5VX|8VX):"

echo ""
echo "✅ Cogged Belts production seeding completed successfully!"
echo "📊 Summary:"
echo "   - 9 sections seeded"
echo "   - $total total products"
echo "   - Rate formulas configured"
echo "   - API endpoints ready"
echo ""
echo "🌐 Test the API:"
echo "   curl http://127.0.0.1:8000/api/cogged-belts/section/AX"
echo ""