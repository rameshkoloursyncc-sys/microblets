# Poly Belt Input Validation Fix

## Problem
The frontend was sending `0` values when users cleared input fields, causing:
- Size to be set to 0 → Rate becomes 0 → Value becomes 0
- UI showing zero values even when backend calculations were correct

## Root Cause
- `v-model.number` converts empty strings to `0`
- No validation to prevent saving invalid values like `0` for size
- Users accidentally clearing fields and triggering saves

## Solution Implemented

### 1. Input Field Changes
- Removed `v-model.number` and used regular `v-model`
- Added `min` attributes to prevent negative values
- Added placeholders for better UX

### 2. Enhanced Validation in `saveCell()`
```javascript
// Check if input is empty before conversion
const inputValue = String(editValue.value).trim()
if (inputValue === '' || inputValue === 'NaN') {
  showNotification('error', 'Invalid Input', `${field} cannot be empty`)
  cancelEdit()
  return
}

val = Number(inputValue)

// Field-specific validation
if (field === 'size' && (val <= 0 || isNaN(val))) {
  showNotification('error', 'Invalid Size', 'Size must be a positive number greater than 0')
  cancelEdit()
  return
}
```

### 3. Enhanced Validation in `performInOut()`
```javascript
const inputValue = String(editValue.value).trim()

if (inputValue === '' || inputValue === 'NaN') {
  showNotification('error', 'Invalid Input', 'Quantity cannot be empty')
  cancelEdit()
  return
}

const qty = Number(inputValue)

if (isNaN(qty) || qty <= 0) {
  showNotification('error', 'Invalid Quantity', 'Quantity must be a positive number')
  cancelEdit()
  return
}
```

## Validation Rules

### Size Field
- ✅ Must be a positive number > 0
- ❌ Cannot be empty, 0, or negative
- ❌ Cannot be NaN or invalid

### Ribs Field  
- ✅ Must be a non-negative number >= 0
- ❌ Cannot be empty or negative
- ❌ Cannot be NaN or invalid

### Rate Field
- ✅ Must be a non-negative number >= 0
- ❌ Cannot be empty or negative
- ❌ Cannot be NaN or invalid

### Section Field
- ✅ Must be a non-empty string
- ❌ Cannot be empty or just whitespace

### IN/OUT Quantities
- ✅ Must be positive numbers > 0
- ❌ Cannot be empty, 0, or negative
- ❌ Cannot be NaN or invalid

## User Experience Improvements

1. **Clear Error Messages**: Users get specific feedback about what went wrong
2. **Input Validation**: Prevents accidental submission of invalid data
3. **Placeholders**: Better guidance on what to enter
4. **Min Attributes**: Browser-level validation for numeric inputs
5. **Trim Whitespace**: Handles accidental spaces in input

## Expected Behavior After Fix

1. ✅ Empty fields show validation error instead of saving as 0
2. ✅ Size changes properly trigger rate recalculation
3. ✅ No more accidental zero values in the table
4. ✅ Clear feedback when user makes input mistakes
5. ✅ Rate and value calculations work correctly

## Testing Scenarios

### Valid Inputs ✅
- Size: 1600 → Rate: 37.17, Value: 371.70 (for 10 ribs)
- Size: 2000 → Rate: 46.46, Value: 464.60 (for 10 ribs)

### Invalid Inputs ❌
- Empty size field → "Size cannot be empty" error
- Size: 0 → "Size must be a positive number greater than 0" error
- Size: -100 → "Size must be a positive number greater than 0" error
- Empty IN quantity → "Quantity cannot be empty" error

The input validation now prevents all the problematic scenarios that were causing zero values in the UI.