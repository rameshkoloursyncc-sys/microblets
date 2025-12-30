#!/bin/bash

# Fix user_id assignments in all belt controllers to use session-based auth

echo "🔧 Fixing user_id assignments in belt controllers..."

# Replace Auth::id() with session('user')['id'] ?? null in all belt controllers
find app/Http/Controllers/Api -name "*BeltController.php" -exec sed -i.bak "s/'user_id' => Auth::id(),/'user_id' => session('user')['id'] ?? null,/g" {} \;

echo "✅ Updated user_id assignments in belt controllers"

# Show what was changed
echo "📋 Files modified:"
find app/Http/Controllers/Api -name "*BeltController.php.bak" -exec basename {} .bak \;

# Clean up backup files
find app/Http/Controllers/Api -name "*BeltController.php.bak" -delete

echo "🧹 Cleaned up backup files"
echo "✅ Auth fix completed!"