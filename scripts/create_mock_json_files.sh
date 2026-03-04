#!/bin/bash
# Create empty mock JSON files for all sections

echo "📦 Creating mock JSON files..."

sections=("A" "B" "C" "DPK" "DPL" "PH" "PJ" "PK" "PL" "PM" "AT10" "AT20" "AT5" "H" "L" "T10" "T14M" "T5M" "T8M_RPP" "T8M" "TS8M" "XL" "5VX")

mkdir -p resources/js/mock

for section in "${sections[@]}"; do
    mock_file="resources/js/mock/${section}Products.json"
    
    if [ -f "$mock_file" ]; then
        echo "⏭️  ${section}Products.json already exists, skipping"
    else
        echo "Creating ${section}Products.json..."
        echo "[]" > "$mock_file"
        echo "✅ Created ${section}Products.json"
    fi
done

echo ""
echo "🎉 All mock JSON files created!"
echo ""
echo "📁 Location: resources/js/mock/"
echo ""
echo "📋 Next steps:"
echo "1. Paste your data into each JSON file"
echo "   Example: resources/js/mock/5VXProducts.json"
echo ""
echo "2. Format (your existing format works):"
echo '   [{"section":"5VX","size":"790","balanceStock":7,"rate":427.39}]'
echo ""
echo "3. Or use full format:"
echo '   [{"id":1,"category":"5VX Section","name":"5VX","sku":"790","size":"790","stock":7,"reorder_level":5,"rate":427.39,"value":2991.73,"in_qty":0,"out_qty":0}]'
echo ""
echo "4. Run create_all_sections.sh to create Vue components"
