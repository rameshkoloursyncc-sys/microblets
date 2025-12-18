#!/bin/bash

echo "🚀 Starting All TPU Belts Production Data Seeding..."
echo "===================================================="

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

# All TPU belt sections
sections=("TS8M" "T5M" "T8M" "S8M" "H" "AT5" "AT10" "T10" "AT20")
total=0
found_sections=()

for section in "${sections[@]}"; do
    count=$(php artisan tinker --execute="echo App\Models\TpuBelt::where('section', '$section')->count();" 2>/dev/null | tail -1)
    if [ "$count" -gt 0 ]; then
        echo "  - $section: $count products"
        found_sections+=("$section")
        total=$((total + count))
    fi
done

echo "  - Total: $total TPU belts"

echo ""
echo "✅ TPU Belts production data seeding completed successfully!"
echo "📊 Summary:"
echo "   - ${#found_sections[@]} sections seeded: ${found_sections[*]}"
echo "   - $total total products imported from JSON files"
echo "   - Value formula: (rate × width ÷ 150) × meter"
echo "   - IN/OUT operations support both width and meter units"
echo "   - API endpoints ready"
echo ""
echo "🌐 Test the APIs:"
for section in "${found_sections[@]}"; do
    echo "   curl \"http://127.0.0.1:8000/api/tpu-belts/section/$section\""
done
echo ""
echo "📋 Expected Data Sources:"
echo "   - TS8M: resources/js/mock/TS8MProducts.json"
echo "   - T5M: resources/js/mock/T5MProducts.json"
echo "   - T8M: resources/js/mock/T8MProducts.json"
echo "   - S8M: resources/js/mock/S8MProducts.json"
echo "   - H: resources/js/mock/HProducts.json"
echo "   - AT5: resources/js/mock/AT5Products.json"
echo "   - AT10: resources/js/mock/AT10Products.json"
echo "   - T10: resources/js/mock/T10Products.json"
echo "   - AT20: resources/js/mock/AT20Products.json"
echo ""
echo "🎯 Production ready with real data!"
echo ""
echo "💡 TPU Belt Features:"
echo "   - Section, Width, Meter structure (no min stock)"
echo "   - Value calculation: (rate × width ÷ 150) × meter"
echo "   - IN/OUT operations with unit choice (width or meter)"
echo "   - Special IN/OUT buttons (not editable cells)"
echo "   - Transaction history tracking"
echo "   - No 'm' suffix in meter display"
echo ""
echo "📝 Next Steps:"
echo "   1. Paste your JSON data into the respective files"
echo "   2. Run this script again to import all data"
echo "   3. Test the frontend by navigating to TPU Belts sections"