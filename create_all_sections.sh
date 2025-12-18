#!/bin/bash
# Create all section tables and mock JSON files from template

echo "🚀 Creating all section tables and mock JSON files..."

sections=("B" "C" "DPK" "DPL" "PH" "PJ" "PK" "PL" "PM" "AT10" "AT20" "AT5" "H" "L" "T10" "T14M" "T5M" "T8M_RPP" "T8M" "TS8M" "XL" "5VX")

for section in "${sections[@]}"; do
    # Create Vue component
    target_file="resources/js/components/inventory/tables/veebelts/${section}_table.vue"
    
    echo "Creating ${section}_table.vue..."
    cp resources/js/components/inventory/tables/SimpleSectionTemplate.vue "$target_file"
    
    # Replace SECTION_NAME in the file
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        sed -i '' "s/CHANGE_ME/${section}/g" "$target_file"
    else
        # Linux
        sed -i "s/CHANGE_ME/${section}/g" "$target_file"
    fi
    
    echo "✅ Created ${section}_table.vue"
    
    # Create mock JSON file (empty array to start)
    mock_file="resources/js/mock/${section}Products.json"
    
    if [ ! -f "$mock_file" ]; then
        echo "Creating ${section}Products.json..."
        echo "[]" > "$mock_file"
        echo "✅ Created ${section}Products.json (empty - paste your data)"
    else
        echo "⏭️  ${section}Products.json already exists, skipping"
    fi
done

echo ""
echo "🎉 All section tables and mock JSON files created!"
echo ""
echo "📋 Next steps:"
echo "1. Paste your data into each JSON file in resources/js/mock/"
echo "   Example format: [{\"section\":\"5VX\",\"size\":\"790\",\"balanceStock\":7,\"rate\":427.39}]"
echo ""
echo "2. Or use the UI to paste JSON:"
echo "   - Visit each section table in your browser"
echo "   - Paste your JSON data in the textarea"
echo "   - Click 'Replace' to import data"
echo "   - Click 'Download JSON' to save the file"
echo "   - Replace the mock JSON file with downloaded file"
echo ""
echo "📁 Vue files: resources/js/components/inventory/tables/veebelts/"
echo "📁 Mock JSON files: resources/js/mock/"