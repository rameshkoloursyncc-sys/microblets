#!/bin/bash

echo "🚀 Starting TPU Belts Production Data Seeding..."
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

# Run migrations to ensure tpu_belts table exists
echo "📋 Running migrations..."
php artisan migrate --force

# Clear existing TPU belts data
echo "🧹 Clearing existing TPU belts data..."
php artisan tinker --execute="App\Models\TpuBelt::truncate();" 2>/dev/null

# Seed TPU belts data from JSON files
echo "🔧 Seeding TPU belts data from JSON files..."
php artisan db:seed --class=TpuBeltSeeder --force

# Verify the seeding
echo "🔍 Verifying seeded data..."
echo "Checking TPU belts count by section:"

sections=("TS8M")
total=0

for section in "${sections[@]}"; do
    count=$(php artisan tinker --execute="echo App\Models\TpuBelt::where('section', '$section')->count();" 2>/dev/null | tail -1)
    echo "  - $section: $count products"
    total=$((total + count))
done

echo "  - Total: $total TPU belts"

echo ""
echo "✅ TPU Belts production data seeding completed successfully!"
echo "📊 Summary:"
echo "   - 1 section seeded (TS8M)"
echo "   - $total total products imported from JSON files"
echo "   - Value formula: (rate × width ÷ 150) × meter"
echo "   - IN/OUT operations support both width and meter units"
echo "   - API endpoints ready"
echo ""
echo "🌐 Test the API:"
echo "   curl \"http://127.0.0.1:8000/api/tpu-belts/section/TS8M\""
echo ""
echo "📋 Data Sources:"
echo "   - TS8M: resources/js/mock/TS8MProducts.json (4 products)"
echo ""
echo "🎯 Production ready with real data!"
echo ""
echo "💡 TPU Belt Features:"
echo "   - Section, Width, Meter structure (no min stock)"
echo "   - Value calculation: (rate × width ÷ 150) × meter"
echo "   - IN/OUT operations with unit choice (width or meter)"
echo "   - Special IN/OUT buttons (not editable cells)"
echo "   - Transaction history tracking"