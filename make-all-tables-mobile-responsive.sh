#!/bin/bash

# Make All Belt Tables Mobile Responsive Script - UPDATED
# Key Change: Only filters are sticky, header and stats scroll normally

echo "🔧 Making All Belt Tables Mobile Responsive (Sticky Filters Only)..."
echo "Date: $(date)"
echo ""

# List of belt table files to update
BELT_TABLES=(
    "resources/js/components/inventory/CoggedBeltTable.vue"
    "resources/js/components/inventory/PolyBeltTable.vue"
    "resources/js/components/inventory/SpecialBeltTable.vue"
    "resources/js/components/inventory/TpuBeltTable.vue"
)

echo "📦 Creating backups..."
for table in "${BELT_TABLES[@]}"; do
    if [ -f "$table" ]; then
        cp "$table" "${table}.backup.$(date +%Y%m%d_%H%M%S)"
        echo "  ✅ Backed up $table"
    else
        echo "  ⚠️  File not found: $table"
    fi
done

echo ""
echo "🎯 Mobile Responsive Status:"
echo "1. ✅ VeeBeltTable.vue - Complete mobile responsive (sticky filters only)"
echo "2. ✅ TimingBeltTable.vue - Complete mobile responsive (sticky filters only)"
echo "3. 🔄 TpuBeltTable.vue - Partial (stats scrollable, needs full update)"
echo "4. ❌ CoggedBeltTable.vue - Needs manual update"
echo "5. ❌ PolyBeltTable.vue - Needs manual update"
echo "6. ❌ SpecialBeltTable.vue - Needs manual update"
echo ""

echo "📱 Key Mobile Responsive Features:"
echo "   ✅ Header scrolls normally (NOT sticky)"
echo "   ✅ Stats cards scroll horizontally on mobile"
echo "   ✅ Only filters are sticky at top"
echo "   ✅ Mobile card view for small screens"
echo "   ✅ Desktop table view for larger screens"
echo "   ✅ Touch-friendly buttons and inputs"
echo ""

echo "🔧 Manual Steps for Remaining Tables:"
echo ""
echo "STEP 1: Update Container Structure"
echo "   Change: <div class=\"p-6 mt-14...\">"
echo "   To:     <div class=\"p-3 sm:p-6 mt-14...\">"
echo ""

echo "STEP 2: Make Header Scrollable (Remove Sticky)"
echo "   Find:    <div class=\"sticky top-14 z-30 bg-gray-50...\">"
echo "            <div class=\"mb-6\">"
echo "              <h1>{{ title }}</h1>"
echo "   Replace: <div class=\"mb-4 sm:mb-6\">"
echo "              <h1 class=\"text-xl sm:text-2xl...\">{{ title }}</h1>"
echo ""

echo "STEP 3: Make Stats Scrollable"
echo "   Find:    <div class=\"mb-4 grid grid-cols-1 md:grid-cols-4 gap-4\">"
echo "   Replace: <div class=\"mb-4 overflow-x-auto\">"
echo "              <div class=\"flex gap-4 pb-2 min-w-max sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:min-w-0\">"
echo ""

echo "STEP 4: Add min-width to Stats Cards"
echo "   Add to each card: min-w-[180px] sm:min-w-0"
echo "   Change text size: text-2xl -> text-xl sm:text-2xl"
echo ""

echo "STEP 5: Make ONLY Filters Sticky"
echo "   Wrap filters section with:"
echo "   <div class=\"sticky top-14 z-30 bg-gray-50 dark:bg-gray-900 pb-4\">"
echo "     <div class=\"mb-4 bg-white dark:bg-gray-800 rounded-lg shadow-md p-3\">"
echo "       <!-- Filter content -->"
echo "     </div>"
echo "   </div>"
echo ""

echo "STEP 6: Update Filter Layout"
echo "   Change: flex flex-wrap items-center gap-2"
echo "   To:     flex flex-col sm:flex-row flex-wrap items-start sm:items-center gap-2"
echo "   Add:    w-full sm:w-auto to inputs and buttons"
echo ""

echo "STEP 7: Add Mobile Card View"
echo "   Add mobile card section: class=\"block md:hidden\""
echo "   Wrap existing table: class=\"hidden md:block\""
echo ""

echo "📋 Template Reference:"
echo "   See mobile-responsive-template.md for complete implementation guide"
echo ""

echo "🧪 Testing Instructions:"
echo "1. Open browser developer tools"
echo "2. Toggle device toolbar (mobile view)"
echo "3. Test scrolling behavior:"
echo "   - Header should scroll away"
echo "   - Stats should scroll away"
echo "   - Filters should stick to top"
echo "4. Test on different screen sizes:"
echo "   - Mobile: 375px width"
echo "   - Tablet: 768px width" 
echo "   - Desktop: 1024px+ width"
echo "5. Verify:"
echo "   - Stats cards scroll horizontally on mobile"
echo "   - Mobile card view shows on small screens"
echo "   - Desktop table shows on large screens"
echo ""

echo "✅ Updated mobile responsive implementation guide complete!"
echo ""
echo "📝 Next Steps:"
echo "1. Apply the template to remaining belt tables manually"
echo "2. Test scrolling behavior - only filters should be sticky"
echo "3. Test on actual mobile devices"
echo "4. Ensure all interactive elements work on touch devices"