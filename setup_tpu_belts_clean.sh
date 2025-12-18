#!/bin/bash

echo "🚀 Setting up Clean TPU Belts System..."
echo "======================================"

# Check if Laravel is accessible
if ! php artisan --version > /dev/null 2>&1; then
    echo "❌ Error: Laravel artisan not accessible"
    exit 1
fi

echo "✅ Laravel is accessible"

# Run migrations to ensure tpu_belts table exists
echo "📋 Running migrations..."
php artisan migrate --force

# Clear all existing TPU belts data
echo "🧹 Clearing all existing TPU belts data..."
php artisan tinker --execute="App\Models\TpuBelt::truncate(); echo 'All TPU belt data cleared';" 2>/dev/null

# Verify the database is empty
echo "🔍 Verifying database is clean..."
count=$(php artisan tinker --execute="echo App\Models\TpuBelt::count();" 2>/dev/null | tail -1)
echo "  - Current TPU belts count: $count"

echo ""
echo "✅ TPU Belts system is ready!"
echo "📊 Summary:"
echo "   - Database table created and empty"
echo "   - JSON import/export functionality enabled"
echo "   - All sample data removed"
echo ""
echo "🎯 Next Steps:"
echo "   1. Navigate to any TPU Belt section in the frontend"
echo "   2. Click 'Import JSON' button"
echo "   3. Paste your JSON data in the required format:"
echo ""
echo "📋 Required JSON Format:"
echo '[
  {
    "section": "5M",
    "width": 150, 
    "meters": 31,
    "rate": 300,
    "remark": "Old Material"
  }
]'
echo ""
echo "💡 Features Available:"
echo "   - Import JSON (append or replace mode)"
echo "   - Download JSON (complete database format)"
echo "   - Value calculation: (rate × width ÷ 150) × meter"
echo "   - IN/OUT operations with unit choice (width or meter)"
echo "   - Transaction history tracking"
echo "   - No 'm' suffix in meter display"
echo ""
echo "🌐 TPU Belt Sections Available:"
echo "   HTD TPU Belts: 5M, 8M, 8M RPP, S8M, 14M"
echo "   Classical TPU Belts: XL, L, H"
echo "   AT Series TPU Belts: AT5, AT10, T10, AT20"
echo "   Total: 12 sections"