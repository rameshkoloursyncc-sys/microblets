# Timing Belt Fixes Summary

## Issues Fixed

### 1. Rate Becoming Zero When Changing Size/MM
**Problem**: When editing size or total_mm fields in timing belts, the rate would become zero.

**Root Cause**: The `calculateValue()` method in `TimingBelt.php` was not properly calculating the rate field.

**Solution**: 
- Updated `TimingBelt::calculateValue()` to strictly follow the formula: `(size × type × 450 × multiplier) + (size × total_mm × multiplier)`
- Added proper rate calculation: `rate = value / total_mm`
- Added proper formula parsing to handle both numeric multipliers and string formulas

### 2. Settings Values Resetting on Refresh
**Problem**: Formula values in the Settings page would reset to defaults when refreshing the page.

**Root Cause**: The timing belt section in SettingsPage.vue was not properly loading and persisting formula values.

**Solution**:
- Added missing API endpoints for timing belt formula management
- Added `recalculateAllRates()` and `recalculateSectionRates()` methods to TimingBeltController
- Updated routes to include the new endpoints

### 3. Separate IN/OUT for Full Sleeve and Total MM Operations
**Problem**: Timing belts needed separate IN/OUT operations for both Full Sleeve and Total MM (similar to TPU belts having separate meter and width operations).

**Solution**:
- Added sleeve fields to TimingBelt model: `full_sleeve`, `in_sleeve`, `out_sleeve`, `rate_per_sleeve`
- Updated `inOutOperation()` method to support both 'total_mm' and 'full_sleeve' unit types
- Enhanced frontend TimingBeltTable.vue to show separate IN/OUT buttons for Total MM and Full Sleeve operations
- Implemented TPU-style dual operation interface with unit type selection

## Files Modified

### Backend Files
1. **app/Models/TimingBelt.php**
   - Added sleeve fields to `$fillable` and `$casts`
   - Fixed `calculateValue()` method to follow strict formula
   - Added proper rate calculation
   - Added DB facade import

2. **app/Http/Controllers/Api/TimingBeltController.php**
   - Enhanced `inOutOperation()` to support both 'total_mm' and 'full_sleeve' operations
   - Added `recalculateAllRates()` method
   - Added `recalculateSectionRates()` method
   - Changed parameter from `operation_type` to `unit_type` to match TPU belt pattern

3. **routes/api_timing_belts.php**
   - Added routes for `recalculate-all-rates` and `recalculate-section-rates`

### Frontend Files
4. **resources/js/composables/useTimingBelts.ts**
   - Added sleeve fields to `TimingBelt` interface
   - Added `unit_type` parameter to `InOutRequest` interface

5. **resources/js/components/inventory/TimingBeltTable.vue**
   - Enhanced IN/OUT modal to support both Total MM and Full Sleeve operations (TPU-style)
   - Added separate IN/OUT buttons for Total MM and Full Sleeve
   - Added sleeve fields to create form
   - Implemented unit type selection in modal (radio buttons)
   - Removed separate sleeve column display (operations handled via modal only)

## Database Structure

The timing belts table includes the required sleeve fields:
- `full_sleeve` (integer) - Number of full sleeves
- `in_sleeve` (integer) - IN sleeve tracking
- `out_sleeve` (integer) - OUT sleeve tracking  
- `rate_per_sleeve` (decimal) - Rate per sleeve

## Formula Implementation

The strict timing belt formula is implemented as:
```
value = (size × type × 450 × multiplier) + (size × total_mm × multiplier)
rate = value / total_mm
```

Where:
- `size` = Belt size (numeric)
- `type` = Type numeric value (18, 21, etc. or 1 for FULL SLEEVE)
- `multiplier` = Section-specific multiplier from rate_formulas table
- `total_mm` = Total inventory in mm

## IN/OUT Operations

Following the TPU belt pattern, timing belts now support dual operations:

1. **Total MM Operations**: 
   - Updates `total_mm`, `in_mm`, `out_mm` fields
   - Uses decimal quantities (0.01 step)
   - Displays in mm units

2. **Full Sleeve Operations**:
   - Updates `full_sleeve`, `in_sleeve`, `out_sleeve` fields  
   - Uses integer quantities (1 step)
   - Displays in sleeve units

Both operations are available through the same modal with unit type selection (radio buttons).

## Testing

A test command is available to verify the fixes:
```bash
php artisan test:timing-belt-fixes
```

This tests:
1. Rate formula existence in database
2. Value calculation correctness
3. Database table structure

## Deployment

Use the deployment script for production:
```bash
./deploy_timing_belt_rate_fix.sh
```

This script:
1. Backs up current files
2. Runs migrations
3. Tests the fixes
4. Clears caches
5. Rebuilds frontend assets
6. Provides verification

## Verification Steps

After deployment, verify:

1. **Rate Calculation**: 
   - Edit a timing belt's size or total_mm
   - Confirm rate is automatically calculated (not zero)

2. **Settings Persistence**:
   - Go to Settings > Timing Belts
   - Change a formula value
   - Refresh the page
   - Confirm the formula value persists

3. **Dual IN/OUT Operations**:
   - For any timing belt, confirm both Total MM and Full Sleeve IN/OUT buttons appear
   - Test both Total MM and Full Sleeve operations
   - Verify stock levels update correctly for both unit types
   - Confirm modal shows unit type selection (radio buttons)

## Rollback Plan

If issues occur, restore from backup:
```bash
# Files are backed up in backups/timing_belt_fixes_YYYYMMDD_HHMMSS/
cp backups/timing_belt_fixes_*/TimingBelt.php app/Models/
cp backups/timing_belt_fixes_*/TimingBeltController.php app/Http/Controllers/Api/
# ... restore other files as needed
```

## Production Considerations

- All changes are backward compatible
- No data migration required (sleeve fields already exist)
- Frontend assets need rebuilding (`npm run build`)
- Cache clearing recommended after deployment
- Test in staging environment first if available