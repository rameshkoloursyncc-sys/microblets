# Timing Belt Value Formula Implementation

## Formula Implemented

**Formula**: `value = (size * type_numeric_value * 450 * multiplier) + (size * total_mm * multiplier)`

Where:
- `size` = belt size (100, 110, 120, etc.)
- `type_numeric_value` = numeric value of the type field
- `450` = fixed constant
- `multiplier` = section-specific multiplier from settings
- `total_mm` = total millimeters in stock

## Section-Specific Multipliers

| Section | Multiplier | Example |
|---------|------------|---------|
| XL | 0.0094 | Commercial XL belts |
| L | 0.0045 | Commercial L belts |
| H | 0.0094 | Commercial H belts |
| XH | 0.0094 | Commercial XH belts |
| T5 | 0.0094 | Commercial T5 belts |
| T10 | 0.0094 | Commercial T10 belts |
| NEOPRENE-XL | 0.0094 | Neoprene XL belts |
| NEOPRENE-L | 0.0045 | Neoprene L belts |
| NEOPRENE-H | 0.0094 | Neoprene H belts |
| NEOPRENE-XH | 0.0094 | Neoprene XH belts |
| NEOPRENE-T5 | 0.0094 | Neoprene T5 belts |
| NEOPRENE-T10 | 0.0094 | Neoprene T10 belts |

## Type Value Conversion

### Commercial Timing Belts
- Uses the actual numeric type value (18, 21, 10, 24, etc.)

### Neoprene Timing Belts
- "FULL SLEEVE" type = 1 (numeric value for calculation)

## Implementation Details

### 1. Database Changes
- ✅ Increased `timing_belts.section` column to `varchar(20)` to accommodate neoprene sections
- ✅ Increased `rate_formulas.section` column to `varchar(20)` for neoprene sections
- ✅ Added timing belt formulas to `rate_formulas` table

### 2. Model Updates
- ✅ Updated `TimingBelt` model with new `calculateValue()` method
- ✅ Added `getTypeNumericValue()` method to handle type conversion
- ✅ Auto-calculation on create/update via model boot method

### 3. Controller Updates
- ✅ Updated validation to allow longer section names (max 20 characters)
- ✅ Maintained existing API endpoints and functionality

### 4. Frontend Updates
- ✅ Updated SettingsPage to include all timing belt sections (including neoprene)
- ✅ Added timing belt formula description: `(size × type × 450 × multiplier) + (size × total_mm × multiplier)`
- ✅ Updated formula display function to show timing belt formula correctly

## Example Calculations

### Commercial Timing Belt (XL Section)
```
Size: 100
Type: 18 (numeric)
Total MM: 4690
Multiplier: 0.0094

Part 1: 100 × 18 × 450 × 0.0094 = 7,614
Part 2: 100 × 4690 × 0.0094 = 4,408.6
Total Value: 7,614 + 4,408.6 = 12,022.6
```

### Neoprene Timing Belt (NEOPRENE-XL Section)
```
Size: 100
Type: FULL SLEEVE (= 1 numeric)
Total MM: 1000
Multiplier: 0.0094

Part 1: 100 × 1 × 450 × 0.0094 = 423
Part 2: 100 × 1000 × 0.0094 = 940
Total Value: 423 + 940 = 1,363
```

## Settings Management

### Access Settings
1. Navigate to Settings page (admin only)
2. Select "Timing Belts" from belt type dropdown
3. View/edit multipliers for each section

### Update Multipliers
- Individual section updates: Click "Update Formula" for specific section
- Bulk updates: Click "Update All Formulas" to apply all changes
- Reset to defaults: Click "Reset to Defaults"
- Recalculate values: Click "Recalculate All Rates" to apply new formulas to existing data

## Files Modified

### Backend
1. `app/Models/TimingBelt.php` - Added new value calculation logic
2. `app/Http/Controllers/Api/TimingBeltController.php` - Updated validation for longer sections
3. `database/migrations/2026_01_05_125122_update_rate_formulas_section_length_for_timing_belts.php` - Database schema updates

### Frontend
1. `resources/js/components/inventory/SettingsPage.vue` - Added timing belt formula support
2. `resources/js/components/inventory/SideBar.vue` - Fixed neoprene XL navigation (from previous fix)

## Testing Results

### ✅ Commercial Timing Belt Test
- Section: XL, Size: 100, Type: 18, Total MM: 4690
- **Before**: Value: 11,725.00
- **After**: Value: 12,022.60
- **Formula Applied**: ✅ Correctly calculated

### ✅ Neoprene Timing Belt Test
- Section: NEOPRENE-XL, Size: 100, Type: FULL SLEEVE, Total MM: 1000
- **Calculated Value**: 1,363.00
- **Formula Applied**: ✅ Correctly calculated with FULL SLEEVE = 1

## Automatic Calculation

The formula is automatically applied when:
- ✅ Creating new timing belt records
- ✅ Updating existing timing belt records (when `total_mm` or `rate` changes)
- ✅ Bulk importing timing belt data
- ✅ Recalculating from Settings page

## Production Deployment

### Safe Deployment Steps
1. **Run Migration**: `php artisan migrate`
2. **Clear Caches**: `php artisan config:clear && php artisan cache:clear`
3. **Test Formula**: Create/update a timing belt record to verify calculation
4. **Bulk Recalculate** (optional): Use Settings page to recalculate all existing values

### Rollback Plan
```sql
-- Revert column lengths if needed
ALTER TABLE timing_belts MODIFY section VARCHAR(10);
ALTER TABLE rate_formulas MODIFY section VARCHAR(10);

-- Remove timing belt formulas
DELETE FROM rate_formulas WHERE category = 'timing_belts';
```

## Benefits

1. **Accurate Pricing**: Values calculated using the exact business formula
2. **Configurable**: Multipliers can be adjusted per section via Settings
3. **Automatic**: No manual calculation needed - values update automatically
4. **Consistent**: Same formula applied across all timing belt types
5. **Auditable**: Formula changes tracked in rate_formulas table

## Next Steps

1. **Verify Production Data**: Test with actual production timing belt data
2. **Train Users**: Show admin users how to update multipliers in Settings
3. **Monitor Values**: Check that calculated values match business expectations
4. **Extend Formula**: Can be easily extended for other belt types if needed

The timing belt value formula is now fully implemented and ready for production use!