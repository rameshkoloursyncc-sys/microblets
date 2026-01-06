# Timing Belts Excel Import System - UPDATED

## Overview
The timing belts Excel import system allows you to import timing belt data from Excel files and export existing data as JSON for seeding in production.

## Recent Fixes (December 30, 2025)

### ✅ Fixed Issues
1. **DateTime Format Error**: Fixed MySQL datetime format issue by using `now()->format('Y-m-d H:i:s')` instead of `now()`
2. **Limited Import Issue**: Enhanced data processing to handle all rows properly (was only importing 15 items)
3. **Better Error Handling**: Added comprehensive debugging and error reporting
4. **Batch Processing**: Added chunked processing for large datasets (50 records per batch)
5. **Data Validation**: Improved row validation and empty row detection

### 🔧 Technical Improvements
- Enhanced `processCommercialTimingBelts()` method with better row processing
- Added debug information in API responses
- Improved empty row detection logic
- Added batch processing for database imports
- Better error messages with line numbers and file details

## Supported Belt Types

### 1. Commercial Timing Belts
**Updated Excel Format (7 columns):**
```
XL | TYPE- 1 (FULL SLEVE) | MM | TOTAL(MM) | RATE PER SLV | TOTAL MM RATE | FINAL VALUE
100 | 18 | 390, 420, 430... | 4690 | 417 | 4346 | 11852
110 | 21 | | 0 | 459 | 0 | 0
120 | 10 | 420, 390, 340... | 3110 | 500 | 3458 | 4459
```

**Important Notes:**
- The "TYPE- 1 (FULL SLEVE)" column contains **numbers** (18, 21, 10, 24, 1, 8, 8) not text
- All commercial timing belts are stored as type "1 (FULL SLEEVE)" in database
- If `size` exists, the row will be imported regardless of other null values
- The `mm` field is not used - only `total_mm` is stored

**Mapping to Database:**
- `section` = Header section (XL, L, 5M, T5, T10, AT5, AT10)
- `size` = XL column value (100, 110, 120, etc.) - **REQUIRED**
- `type` = "1 (FULL SLEEVE)" (always set for commercial)
- `total_mm` = TOTAL(MM) column
- `value` = FINAL VALUE column
- `remark` = MM column (comma-separated values)
- `rate` = RATE PER SLV column
- `in_mm` = 0 (default)
- `out_mm` = 0 (default)
- `reorder_level` = null (default)

### 2. Neoprene Timing Belts
**Excel Format:**
```
FULL SLEEVE | MM | RATE PER SLEEVE | TOTAL RATE
150 | 100 | 25.50 | 2550
160 | 120 | 28.75 | 3450
170 | 80 | 22.00 | 1760
```

**Mapping to Database:**
- `section` = "NEOPRENE"
- `size` = FULL SLEEVE column
- `type` = "1 (FULL SLEEVE)"
- `total_mm` = MM column (no separate mm field)
- `rate` = RATE PER SLEEVE column
- `value` = TOTAL RATE column
- `remark` = "Neoprene timing belt"
- `in_mm` = 0 (default)
- `out_mm` = 0 (default)
- `reorder_level` = null (default)

## How to Use

### 1. Access the Import Feature
1. Go to Timing Belts section in the inventory
2. Click "📊 Import Excel" button
3. Select belt type (Commercial or Neoprene)
4. Upload your Excel file (.xlsx or .xls)

### 2. Process Excel File
1. Click "Process Excel" to parse the file
2. Review the preview data
3. Check that section, size, and values are correct
4. Click "Import to Database" to save to database

### 3. Download JSON for Production
1. After importing, click "📄 Download JSON" button
2. This downloads a JSON file with all timing belts data
3. Use this JSON file to seed data in production

## API Endpoints

### Upload Excel File
```
POST /api/timing-belts/upload-excel
Content-Type: multipart/form-data

Parameters:
- excel_file: Excel file (.xlsx, .xls)
- belt_type: "commercial" or "neoprene"
```

### Import to Database
```
POST /api/timing-belts/import-to-database
Content-Type: application/json

Body:
{
  "data": [...], // Array of timing belt objects
  "section": "XL" // Section name
}
```

### Download JSON
```
GET /api/timing-belts/download-json?section=XL
```

## File Structure

### Controller
`app/Http/Controllers/TimingBeltExcelController.php`
- Handles Excel file processing
- Converts Excel data to database format
- Manages import/export operations

### Frontend Component
`resources/js/components/inventory/TimingBeltTable.vue`
- Excel import modal
- File upload handling
- Preview functionality
- JSON download

### Routes
`routes/api.php`
- Excel import/export routes
- Authentication middleware

## Sample Excel Files

### Commercial Timing Belts (XL Section)
```csv
XL,TYPE- 1 (FULL SLEVE),MM,TOTAL(MM),RATE PER SLV,TOTAL RATE,PER MM RATE,TOTAL MM RATE,FINAL VALUE
100,1 (FULL SLEEVE),"390, 420, 430, 430, 430, 430, 440, 440, 320, 320, 330, 310",4690,417,7506,0.93,4346,11852
110,1 (FULL SLEEVE),,0,459,0,1.02,0,0
120,1 (FULL SLEEVE),"420, 390, 340, 380, 260, 240, 100, 100, 440, 440",3110,500,1001,1.11,3458,4459
```

### Neoprene Timing Belts
```csv
FULL SLEEVE,MM,RATE PER SLEEVE,TOTAL RATE
150,100,25.50,2550
160,120,28.75,3450
170,80,22.00,1760
```

## Supported Sections

### Commercial Sections
- **XL**: Extra Large timing belts
- **L**: Large timing belts  
- **5M**: 5mm pitch timing belts
- **T5**: T5 profile timing belts
- **T10**: T10 profile timing belts
- **AT5**: AT5 profile timing belts
- **AT10**: AT10 profile timing belts

### Neoprene Sections
- **NEOPRENE**: All neoprene timing belts

## Error Handling

### Common Errors
1. **"Could not identify section from Excel headers"**
   - Solution: Ensure header contains XL, L, 5M, T5, T10, AT5, or AT10

2. **"Failed to process Excel file"**
   - Solution: Check Excel format matches expected structure
   - Ensure file is .xlsx or .xls format

3. **"No data to import"**
   - Solution: Process Excel file first before importing

### Validation Rules
- Excel file must be .xlsx or .xls format
- Belt type must be "commercial" or "neoprene"
- Section must be identifiable from headers
- Size and total_mm values are required for commercial belts
- FULL SLEEVE value is required for neoprene belts

## Production Deployment

### 1. Seeding Data
```bash
# After importing and downloading JSON
php artisan tinker
>>> $data = json_decode(file_get_contents('timing_belts_XL_2025-12-30.json'), true);
>>> DB::table('timing_belts')->insert($data);
```

### 2. Bulk Import Script
```php
// Create a seeder command
php artisan make:command SeedTimingBelts

// In the command:
$jsonFiles = [
    'timing_belts_XL.json',
    'timing_belts_L.json',
    'timing_belts_5M.json',
    // ... other sections
];

foreach ($jsonFiles as $file) {
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        DB::table('timing_belts')->insert($data);
        $this->info("Imported {$file}");
    }
}
```

## Best Practices

### 1. Data Preparation
- Clean Excel data before import
- Ensure consistent formatting
- Remove empty rows
- Validate section headers

### 2. Import Process
- Always preview data before importing
- Import one section at a time
- Backup existing data before bulk imports
- Test with small datasets first

### 3. Production Deployment
- Download JSON files for each section
- Store JSON files in version control
- Use seeder commands for consistent deployment
- Validate data after import

## Troubleshooting

### Recently Fixed Issues

#### 1. DateTime Format Error
**Error:** `SQLSTATE[22007]: Invalid datetime format: 1292 Incorrect datetime value: '2025-12-30T08:29:44.441534Z'`

**Solution:** ✅ Fixed in controller by using `now()->format('Y-m-d H:i:s')` instead of `now()`

#### 2. Limited Import (Only 15 Items)
**Error:** Only importing 15 items instead of expected 28-30

**Solutions Applied:**
- ✅ Enhanced empty row detection logic
- ✅ Improved data validation (check for essential fields)
- ✅ Added better debugging information
- ✅ Fixed row processing logic to handle all valid rows

#### 3. Axios Import Error
**Error:** `ReferenceError: axios is not defined`

**Solution:** ✅ Axios is properly imported in component. If error persists:
```bash
# Rebuild frontend assets
npm run build
# or for development
npm run dev
```

### Excel File Issues
```bash
# Check file format
file your-excel-file.xlsx

# Convert CSV to Excel if needed
# Use Excel or LibreOffice to save as .xlsx
```

### Database Issues
```bash
# Check timing_belts table structure
php artisan tinker
>>> Schema::getColumnListing('timing_belts')

# Check existing data
>>> DB::table('timing_belts')->count()
>>> DB::table('timing_belts')->first()
```

### API Testing
```bash
# Test Excel upload (requires authentication)
curl -X POST \
  -H "Cookie: laravel_session=your-session" \
  -F "excel_file=@sample_timing_belts_commercial.xlsx" \
  -F "belt_type=commercial" \
  http://localhost:8000/api/timing-belts/upload-excel

# Test JSON download
curl -H "Cookie: laravel_session=your-session" \
  "http://localhost:8000/api/timing-belts/download-json?section=XL"
```

## Future Enhancements

### Planned Features
1. **Batch Processing**: Import multiple Excel files at once
2. **Data Validation**: Advanced validation rules
3. **Duplicate Detection**: Prevent duplicate entries
4. **Update Mode**: Update existing records instead of replacing
5. **Export to Excel**: Export database data back to Excel format
6. **Template Generator**: Generate Excel templates for different sections

### Integration Ideas
1. **Email Notifications**: Send import status via email
2. **Audit Trail**: Track who imported what data
3. **Scheduled Imports**: Automatic import from shared folders
4. **API Integration**: Connect with external inventory systems

This system provides a complete solution for managing timing belts data import/export with Excel files while maintaining data integrity and providing a user-friendly interface.