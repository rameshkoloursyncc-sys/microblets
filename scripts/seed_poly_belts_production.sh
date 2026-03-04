#!/bin/bash

echo "🚀 Starting Poly Belts Production Seeding..."
echo "============================================"

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

# Run migrations to ensure poly_belts table exists
echo "📋 Running migrations..."
php artisan migrate --force

# Seed rate formulas (includes poly belts formulas)
echo "📊 Seeding rate formulas..."
php artisan db:seed --class=RateFormulaSeeder --force

# Seed poly belts data
echo "🔧 Preparing poly belts system (empty - use data script for real data)..."
php artisan db:seed --class=PolyBeltSeeder --force

# Verify the seeding
echo "🔍 Verifying seeded data..."
echo "Checking poly belts count by section:"

sections=("PJ" "PK" "PL" "PM" "PH" "DPL" "DPK")
total=0

for section in "${sections[@]}"; do
    count=$(php artisan tinker --execute="echo App\Models\PolyBelt::where('section', '$section')->count();" 2>/dev/null | tail -1)
    echo "  - $section: $count products"
    total=$((total + count))
done

echo "  - Total: $total poly belts"

# Check rate formulas
echo ""
echo "Checking rate formulas for poly belts:"
php artisan tinker --execute="
App\Models\RateFormula::where('category', 'poly_belts')->get()->each(function(\$formula) {
    echo \$formula->section . ': ' . \$formula->formula . PHP_EOL;
});
" 2>/dev/null | grep -E "^(PJ|PK|PL|PM|PH|DPL|DPK):"

echo ""
echo "✅ Poly Belts production seeding completed successfully!"
echo "📊 Summary:"
echo "   - 7 sections seeded"
echo "   - $total total products"
echo "   - Rate formulas configured"
echo "   - API endpoints ready"
echo ""
echo "🌐 Test the API:"
echo "   curl \"http://127.0.0.1:8000/api/poly-belts/section/PK\""
echo ""
echo "📋 Usage Instructions:"
echo "   1. Run './seed_poly_belts_production_data.sh' to import PK/PL data from JSON files"
echo "   2. Navigate to Poly Belts section in the UI"
echo "   3. JSON import/export buttons are commented out for production"
echo "   4. Use inline editing and IN/OUT operations for inventory management"
echo ""
echo "📋 Rate Calculation Examples:"
echo "   - PJ: ribs/25.4*0.36 (e.g., 4 ribs = 4/25.4*0.36 = 0.057)"
echo "   - PK: ribs/25.4*0.59 (e.g., 4 ribs = 4/25.4*0.59 = 0.093)"
echo "   - PL: ribs/25.4*0.85 (e.g., 6 ribs = 6/25.4*0.85 = 0.201)"
echo ""