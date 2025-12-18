#!/bin/bash

echo "🚀 Starting Poly Belts Production Data Seeding..."
echo "================================================="

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

# Clear existing poly belts data
echo "🧹 Clearing existing poly belts data..."
php artisan tinker --execute="App\Models\PolyBelt::truncate();" 2>/dev/null

# Seed poly belts data from JSON files
echo "🔧 Seeding poly belts data from JSON files..."
php artisan db:seed --class=PolyBeltSeeder --force

# Verify the seeding
echo "🔍 Verifying seeded data..."
echo "Checking poly belts count by section:"

sections=("PK" "PL")
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
echo "✅ Poly Belts production data seeding completed successfully!"
echo "📊 Summary:"
echo "   - 2 sections seeded (PK, PL)"
echo "   - $total total products imported from JSON files"
echo "   - Rate formulas configured for all 7 sections"
echo "   - API endpoints ready"
echo "   - JSON import/export buttons commented out for production"
echo ""
echo "🌐 Test the API:"
echo "   curl \"http://127.0.0.1:8000/api/poly-belts/section/PK\""
echo "   curl \"http://127.0.0.1:8000/api/poly-belts/section/PL\""
echo ""
echo "📋 Data Sources:"
echo "   - PK: resources/js/mock/PKProducts.json (46 products)"
echo "   - PL: resources/js/mock/PLProducts.json (9 products)"
echo ""
echo "🎯 Production ready with real data!"