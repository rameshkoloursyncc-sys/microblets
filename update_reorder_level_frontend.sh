#!/bin/bash

# Update all belt table components to handle null reorder_level values

echo "Updating belt table components for null reorder_level handling..."

# List of belt table files
files=(
    "resources/js/components/inventory/CoggedBeltTable.vue"
    "resources/js/components/inventory/PolyBeltTable.vue"
    "resources/js/components/inventory/TpuBeltTable.vue"
    "resources/js/components/inventory/TimingBeltTable.vue"
    "resources/js/components/inventory/SpecialBeltTable.vue"
)

for file in "${files[@]}"; do
    echo "Updating $file..."
    
    # Update low stock filter logic
    sed -i '' 's/p\.balance_stock <= p\.reorder_level && p\.balance_stock > 0/p.reorder_level !== null \&\& p.reorder_level > 1 \&\& p.balance_stock <= p.reorder_level \&\& p.balance_stock > 0/g' "$file"
    
    # Update low stock count logic
    sed -i '' 's/p\.balance_stock <= p\.reorder_level && p\.balance_stock > 0/p.reorder_level !== null \&\& p.reorder_level > 1 \&\& p.balance_stock <= p.reorder_level \&\& p.balance_stock > 0/g' "$file"
    
    # Update stock class logic
    sed -i '' 's/p\.balance_stock <= p\.reorder_level/p.reorder_level !== null \&\& p.reorder_level > 1 \&\& p.balance_stock <= p.reorder_level/g' "$file"
    
    # Update display of reorder_level
    sed -i '' 's/{{ p\.reorder_level }}/{{ p.reorder_level ?? '\''Not tracked'\'' }}/g' "$file"
    
    # Update createForm default
    sed -i '' 's/reorder_level: 5,/reorder_level: null,/g' "$file"
    
    # Update form label
    sed -i '' 's/Minimum Inventory Level/Minimum Inventory Level (leave empty for no tracking)/g' "$file"
    sed -i '' 's/min="0" \/>/min="0" placeholder="Leave empty to disable tracking" \/>/g' "$file"
    
    echo "Updated $file"
done

echo "All belt table components updated!"