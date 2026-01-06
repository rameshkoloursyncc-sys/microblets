# Timing Belt Formula Fix - Auto-Recalculation Issue

## Issue Fixed
The timing belt value was not recalculating automatically when `size` or `type` fields were changed.

## Root Causes
1. **Model Boot Method**: Only triggered recalculation on `total_mm` and `rate` changes, not `size` or `type`
2. **Database Formula Format**: Formulas were stored as complex strings instead of simple multiplier values

## Solutions Applied

### 1. Updated Model Boot Method
**File**: `app/Models/TimingBelt.php`

**Before**:
```php
if ($timingBelt->isDirty(['total_mm', 'rate'])) {
    $timingBelt->calculateValue();
}
```

**After**:
```php
if ($timingBelt->isDirty(['size', 'type', 'total_mm', 'rate'])) {
    $timingBelt->calculateValue();
}
```

### 2. Fixed Database Formula Format
**Before**: Formulas stored as `"size/1*0.0094"` (complex string)
**After**: Formulas stored as `"0.0094"` (simple multiplier value)

Updated all timing belt sections:
- XL: 0.0094
- L: 0.0045  
- H: 0.0094
- XH: 0.0094
- T5: 0.0094
- T10: 0.0094
- 5M: 0.0094
- 8M: 0.0094
- 14M: 0.0094
- DL: 0.0094
- DH: 0.0094
- D5M: 0.0094
- D8M: 0.0094
- NEOPRENE-XL: 0.0094
- NEOPRENE-L: 0.0045
- NEOPRENE-H: 0.0094
- NEOPRENE-XH: 0.0094
- NEOPRENE-T5: 0.0094
- NEOPRENE-T10: 0.0094

## Testing Results

### ✅ Commercial Timing Belt (XL Section)
- **Size Change**: 100 → 150 ✅ Value recalculated correctly
- **Type Change**: 18 → 20 ✅ Value recalculated correctly
- **Formula**: (150 × 20 × 450 × 0.0094) + (150 × 4690 × 0.0094) = 19,302.90

### ✅ Neoprene Timing Belt (NEOPRENE-XL Section)  
- **Size Change**: 100 → 120 ✅ Value recalculated correctly
- **Type**: FULL SLEEVE (= 1 numeric) ✅ Handled correctly
- **Formula**: (120 × 1 × 450 × 0.0094) + (120 × 1000 × 0.0094) = 1,635.60

## Auto-Recalculation Triggers

The value now automatically recalculates when any of these fields change:
- ✅ `size` - Belt size (100, 110, 120, etc.)
- ✅ `type` - Type value (18, 21, 10, 24, or "FULL SLEEVE")
- ✅ `total_mm` - Total millimeters in stock
- ✅ `rate` - Rate per unit (though not used in new formula)

## Formula Applied
```
value = (size × type_numeric_value × 450 × multiplier) + (size × total_mm × multiplier)
```

Where:
- `type_numeric_value` = actual numeric value for commercial belts, 1 for neoprene "FULL SLEEVE"
- `multiplier` = section-specific multiplier from database (0.0094 for XL, 0.0045 for L, etc.)

## Status: ✅ RESOLVED
The timing belt value calculation now works correctly and automatically updates when any relevant field is changed.