#!/bin/bash
# Master script to setup all sections at once

echo "🚀 Setting up all 50+ sections..."
echo ""

# Step 1: Create mock JSON files
echo "Step 1: Creating mock JSON files..."
./create_mock_json_files.sh
echo ""

# Step 2: Create Vue components
echo "Step 2: Creating Vue components..."
./create_all_sections.sh
echo ""

echo "✅ Setup complete!"
echo ""
echo "📋 What was created:"
echo "   - 23 mock JSON files in resources/js/mock/"
echo "   - 23 Vue components in resources/js/components/inventory/tables/veebelts/"
echo ""
echo "📝 Next steps:"
echo ""
echo "1. Paste your data into each JSON file:"
echo "   Example: resources/js/mock/5VXProducts.json"
echo ""
echo "2. Your data format (will be auto-converted):"
echo '   [{"section":"5VX","size":"790","balanceStock":7,"rate":427.39}]'
echo ""
echo "3. Or use the UI:"
echo "   - Visit each section in browser"
echo "   - Paste JSON in textarea"
echo "   - Click 'Replace' to import"
echo "   - Click 'Download JSON' to save"
echo "   - Replace mock file with downloaded file"
echo ""
echo "4. Commit to git:"
echo "   git add resources/js/mock/*.json"
echo "   git add resources/js/components/inventory/tables/veebelts/*_table.vue"
echo "   git commit -m 'Add all section tables with mock data'"
echo ""
echo "📖 Read SIMPLE_SECTION_WORKFLOW.md for detailed instructions"
