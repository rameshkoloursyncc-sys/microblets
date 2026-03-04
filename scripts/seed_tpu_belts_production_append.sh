#!/bin/bash

echo "🚀 Starting TPU Belts Production Data Append..."
echo "=============================================="

# Check if Laravel is accessible
if ! php artisan --version > /dev/null 2>&1; then
    echo "❌ Error: Laravel artisan not accessible"
    exit 1
fi

echo "✅ Laravel is accessible"

# Run migrations to ensure tpu_belts table exists
echo "📋 Running migrations..."
php artisan migrate --force

# Function to import TPU data from JSON file
import_tpu_section() {
    local section=$1
    local filename=$2
    local filepath="resources/js/mock/$filename"
    
    if [ ! -f "$filepath" ]; then
        echo "⚠️  File not found: $filename"
        return
    fi
    
    echo "📥 Importing $section from $filename..."
    
    # Read JSON and import via API
    php artisan serve --host=127.0.0.1 --port=8001 > /dev/null 2>&1 &
    SERVER_PID=$!
    sleep 2
    
    # Import the data
    curl -s -X POST "http://127.0.0.1:8001/api/tpu-belts/bulk-import" \
        -H "Content-Type: application/json" \
        -d "{\"data\": $(cat $filepath), \"mode\": \"append\"}" > /dev/null
    
    # Stop the server
    kill $SERVER_PID 2>/dev/null
    wait $SERVER_PID 2>/dev/null
    
    # Count imported items
    local count=$(php artisan tinker --execute="echo App\Models\TpuBelt::where('section', '$section')->count();" 2>/dev/null | tail -1)
    echo "✅ $section: $count products"
}

echo "🔧 Importing TPU belt data from JSON files..."

# Import all TPU sections
import_tpu_section "5M" "TPU5MProducts.json"
import_tpu_section "8M" "TPU8MProducts.json"
import_tpu_section "S8M" "TPUS8MProducts.json"
import_tpu_section "H" "TPUHProducts.json"
import_tpu_section "AT10" "TPUAT10Products.json"
import_tpu_section "T10" "TPUT10Products.json"
import_tpu_section "AT20" "TPUAT20Products.json"

# Verify the final count
echo ""
echo "🔍 Final verification..."
total=$(php artisan tinker --execute="echo App\Models\TpuBelt::count();" 2>/dev/null | tail -1)
echo "📊 Total TPU belts in database: $total"

echo ""
echo "✅ TPU Belts production data append completed!"
echo "📋 Available sections with data:"

sections=("5M" "8M" "S8M" "H" "AT10" "T10" "AT20")
for section in "${sections[@]}"; do
    count=$(php artisan tinker --execute="echo App\Models\TpuBelt::where('section', '$section')->count();" 2>/dev/null | tail -1)
    if [ "$count" -gt 0 ]; then
        echo "   - $section: $count products"
    fi
done

echo ""
echo "🎯 All TPU belt data has been appended to the database!"
echo "💡 Use the Settings page to manage rates and seed additional data"