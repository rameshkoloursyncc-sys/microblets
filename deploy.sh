#!/bin/bash

# Build frontend assets
npm run build

# Create production artifact
rm -rf ./deploy
mkdir deploy
mkdir deploy/public

# Copy necessary files and directories
cp -r app deploy/
cp -r bootstrap deploy/
cp -r config deploy/
cp -r database deploy/
cp -r lang deploy/
cp -r public/* deploy/public/
cp -r resources deploy/
cp -r routes deploy/
cp -r storage deploy/
cp -r vendor deploy/
cp .env.production deploy/.env
cp artisan deploy/
cp composer.json deploy/
cp composer.lock deploy/
cp package.json deploy/

# Create storage symlink directory structure
mkdir -p deploy/public/storage

# Create required directories with proper permissions
mkdir -p deploy/storage/framework/{sessions,views,cache}
mkdir -p deploy/storage/logs
chmod -R 775 deploy/storage

# Create zip file for upload
cd deploy && zip -r ../deploy.zip . && cd ..

echo "Deployment package created: deploy.zip"
echo ""
echo "cPanel Deployment Instructions:"
echo "1. Login to your cPanel account"
echo "2. Go to File Manager"
echo "3. Navigate to public_html or your desired subdirectory"
echo "4. Upload and extract deploy.zip"
echo "5. Through SSH or Terminal in cPanel, run:"
echo "   php artisan key:generate"
echo "   php artisan migrate"
echo "   php artisan storage:link"
echo "6. Update .env with your database credentials"
echo "7. Ensure proper permissions:"
echo "   chmod -R 755 ."
echo "   chmod -R 775 storage bootstrap/cache"
echo ""
echo "Note: Make sure to update your domain in .env file"