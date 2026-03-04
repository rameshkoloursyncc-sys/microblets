#!/bin/bash

# Deploy Timing Belt JSON Files to Production
# This script copies all timing belt JSON files to the production server

echo "🚀 Deploying Timing Belt JSON Files to Production..."

# List of timing belt JSON files to deploy
JSON_FILES=(
    "Timing3MProducts.json"
    "Timing5MProducts.json"
    "Timing8MProducts.json"
    "Timing14MProducts.json"
    "TimingDLProducts.json"
    "TimingDHProducts.json"
    "TimingD5MProducts.json"
    "TimingD8MProducts.json"
    "Neoprene3MProducts.json"
    "Neoprene5MProducts.json"
    "Neoprene8MProducts.json"
    "Neoprene14MProducts.json"
    "NeopreneDLProducts.json"
    "NeopreneDHProducts.json"
    "NeopreneD5MProducts.json"
    "NeopreneD8MProducts.json"
)

# Production server details
PROD_SERVER="ubuntu@16.171.133.192"
PROD_PATH="/var/www/microbelts_ima/resources/js/mock/"
LOCAL_PATH="resources/js/mock/"

echo "📁 Copying JSON files to production..."

for file in "${JSON_FILES[@]}"; do
    if [ -f "${LOCAL_PATH}${file}" ]; then
        echo "  ✅ Copying ${file}..."
        scp "${LOCAL_PATH}${file}" "${PROD_SERVER}:${PROD_PATH}${file}"
    else
        echo "  ❌ File not found: ${file}"
    fi
done

echo "🎉 Deployment complete!"
echo ""
echo "📋 Next steps:"
echo "1. SSH to production server: ssh ${PROD_SERVER}"
echo "2. Check files exist: ls -la ${PROD_PATH}*Products.json"
echo "3. Test seeding in Settings page"