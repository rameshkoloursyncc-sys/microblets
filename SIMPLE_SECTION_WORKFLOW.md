# Simple Section Workflow (Like SPA Section)

## 🎯 Overview

This approach uses **mock JSON files** (like `spaProducts.json`) for each section. No backend, no database - just simple JSON files that are part of your build.

## 📁 File Structure

```
resources/js/
├── mock/
│   ├── spaProducts.json          ← Existing (reference)
│   ├── 5VXProducts.json          ← New (example created)
│   ├── AProducts.json            ← Create for A section
│   ├── BProducts.json            ← Create for B section
│   └── ...                       ← One file per section
└── components/inventory/tables/
    ├── SimpleSectionTemplate.vue  ← Template (copy this)
    ├── FlowbiteTable_clean.vue   ← SPA section (reference)
    └── veebelts/
        ├── 5VX_table.vue         ← New (example created)
        ├── A_table.vue           ← Create for A section
        ├── B_table.vue           ← Create for B section
        └── ...                   ← One file per section
```

## 🚀 Quick Start: Create One Section

### Example: Create 5VX Section

**Step 1: Create Mock JSON File**
```bash
# Create the JSON file
cat > resources/js/mock/5VXProducts.json << 'EOF'
[
  {
    "id": 1,
    "category": "5VX Section",
    "name": "5VX",
    "sku": "790",
    "size": "790",
    "stock": 7,
    "reorder_level": 5,
    "rate": 427.39,
    "value": 2991.73,
    "in_qty": 0,
    "out_qty": 0
  }
]
EOF
```

**Step 2: Create Vue Component**
```bash
# Copy template
cp resources/js/components/inventory/tables/SimpleSectionTemplate.vue \
   resources/js/components/inventory/tables/veebelts/5VX_table.vue

# Edit the file and change ONLY this line:
# const SECTION_NAME = '5VX'
```

**Step 3: Done!**
- The component will automatically load from `5VXProducts.json`
- All CRUD operations work
- Data persists in localStorage during session
- Download JSON to update the mock file permanently

## 📋 Your Data Format

You can paste data in **your existing format**:

```json
[
  {"section":"5VX","size":"790","balanceStock":7,"rate":427.39,"value":2991.73},
  {"section":"5VX","size":"710","balanceStock":1,"rate":384.11,"value":384.11}
]
```

The template will auto-convert to:

```json
[
  {
    "id": 1,
    "category": "5VX Section",
    "name": "5VX",
    "sku": "790",
    "size": "790",
    "stock": 7,
    "reorder_level": 5,
    "rate": 427.39,
    "value": 2991.73,
    "in_qty": 0,
    "out_qty": 0
  }
]
```

## 🔄 Workflow for Updating Data

### Option 1: Direct File Edit (Recommended for Initial Setup)
1. Edit `resources/js/mock/5VXProducts.json` directly
2. Paste your JSON data
3. Save file
4. Refresh browser

### Option 2: UI Paste + Download (Recommended for Updates)
1. Visit section table in browser
2. Paste JSON in textarea
3. Click "Replace" or "Append"
4. Make any edits in the UI
5. Click "Download JSON"
6. Replace the mock file with downloaded file
7. Commit to git

## 🔧 Batch Create All Sections

Run the script to create all 50+ sections at once:

```bash
chmod +x create_all_sections.sh
./create_all_sections.sh
```

This creates:
- ✅ All Vue components in `resources/js/components/inventory/tables/veebelts/`
- ✅ All mock JSON files in `resources/js/mock/` (empty arrays)

Then paste your data into each JSON file.

## 📝 Sections to Create

Based on your requirements, create these files:

### V-Belt Sections
- `AProducts.json` + `A_table.vue`
- `BProducts.json` + `B_table.vue`
- `CProducts.json` + `C_table.vue`

### Poly Belt Sections
- `DPKProducts.json` + `DPK_table.vue`
- `DPLProducts.json` + `DPL_table.vue`
- `PHProducts.json` + `PH_table.vue`
- `PJProducts.json` + `PJ_table.vue`
- `PKProducts.json` + `PK_table.vue`
- `PLProducts.json` + `PL_table.vue`
- `PMProducts.json` + `PM_table.vue`

### TPU Belt Sections
- `AT10Products.json` + `AT10_table.vue`
- `AT20Products.json` + `AT20_table.vue`
- `AT5Products.json` + `AT5_table.vue`
- `HProducts.json` + `H_table.vue`
- `LProducts.json` + `L_table.vue`
- `T10Products.json` + `T10_table.vue`
- `T14MProducts.json` + `T14M_table.vue`
- `T5MProducts.json` + `T5M_table.vue`
- `T8M_RPPProducts.json` + `T8M_RPP_table.vue`
- `T8MProducts.json` + `T8M_table.vue`
- `TS8MProducts.json` + `TS8M_table.vue`
- `XLProducts.json` + `XL_table.vue`

### Special Sections
- `5VXProducts.json` + `5VX_table.vue` ✅ (Already created as example)

## ✨ Features

Each section table has:

1. **JSON Paste Import**
   - Paste array of objects
   - Append or Replace mode
   - Auto-converts your format to standard format

2. **Download JSON**
   - Export current data as JSON
   - Use to update mock file permanently

3. **Full CRUD**
   - Click any cell to edit
   - Create new products
   - Delete products

4. **IN/OUT Operations**
   - Bulk select products
   - Add stock (IN)
   - Remove stock (OUT)
   - Low stock alerts

5. **Search & Filter**
   - Search by name or size
   - Filter by stock levels

6. **Data Persistence**
   - Initial load from mock JSON file
   - Session updates in localStorage
   - Download to update mock file permanently

## 🎯 Benefits

1. **No Backend** - Pure frontend solution
2. **Simple** - Just JSON files and Vue components
3. **Scalable** - One template for 50+ sections
4. **Deployment Safe** - Mock files are part of build
5. **Git Friendly** - JSON files can be version controlled
6. **Same as SPA** - Uses proven pattern from existing code

## 🚨 Important Notes

- Each section is completely independent
- Mock JSON files are loaded at build time
- localStorage is used for session persistence only
- To make changes permanent, download JSON and update mock file
- Missing fields in your JSON will default to 0 or empty string
- The template handles all conversions automatically

## 🔍 Example: Complete Setup for 5VX Section

**1. Create mock file:**
```bash
echo '[]' > resources/js/mock/5VXProducts.json
```

**2. Create component:**
```bash
cp resources/js/components/inventory/tables/SimpleSectionTemplate.vue \
   resources/js/components/inventory/tables/veebelts/5VX_table.vue
```

**3. Edit component (change one line):**
```javascript
const SECTION_NAME = '5VX'
```

**4. Paste your data in UI or directly in JSON file:**
```json
[
  {"section":"5VX","size":"790","balanceStock":7,"rate":427.39,"value":2991.73}
]
```

**5. Done!** Visit the section in browser and start using it.

## 📞 Need Help?

- Check `resources/js/components/inventory/FlowbiteTable_clean.vue` for SPA section reference
- Check `resources/js/mock/spaProducts.json` for JSON format reference
- Check `resources/js/lib/api/inventoryApi.ts` for how SPA loads mock data
