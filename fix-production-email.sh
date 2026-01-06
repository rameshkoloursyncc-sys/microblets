#!/bin/bash

# Production Email Fix Script
# This script helps diagnose and fix email issues in production

echo "🔧 Production Email Troubleshooting"
echo "==================================="

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: This doesn't appear to be a Laravel project directory."
    echo "Please run this script from your Laravel project root."
    exit 1
fi

echo "📁 Project Path: $(pwd)"
echo ""

# Step 1: Check PHP extensions
echo "🔍 Step 1: Checking PHP Extensions"
echo "-----------------------------------"

required_extensions=("openssl" "mbstring" "curl")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✅ $ext: Installed"
    else
        echo "❌ $ext: Missing"
        missing_extensions+=("$ext")
    fi
done

if [ ${#missing_extensions[@]} -gt 0 ]; then
    echo ""
    echo "⚠️  Missing extensions detected. Install with:"
    echo "sudo apt-get update"
    for ext in "${missing_extensions[@]}"; do
        echo "sudo apt-get install php-$ext"
    done
    echo ""
fi

# Step 2: Check SMTP connectivity
echo "🌐 Step 2: Testing SMTP Connectivity"
echo "------------------------------------"

# Test port 587
if timeout 5 bash -c "</dev/tcp/smtp.gmail.com/587" 2>/dev/null; then
    echo "✅ Port 587: Accessible"
else
    echo "❌ Port 587: Not accessible"
fi

# Test port 465
if timeout 5 bash -c "</dev/tcp/smtp.gmail.com/465" 2>/dev/null; then
    echo "✅ Port 465: Accessible"
else
    echo "❌ Port 465: Not accessible"
fi

echo ""

# Step 3: Check Laravel configuration
echo "📧 Step 3: Laravel Email Configuration"
echo "--------------------------------------"

php artisan tinker --execute="
echo 'MAIL_MAILER: ' . config('mail.default') . PHP_EOL;
echo 'MAIL_HOST: ' . config('mail.mailers.smtp.host') . PHP_EOL;
echo 'MAIL_PORT: ' . config('mail.mailers.smtp.port') . PHP_EOL;
echo 'MAIL_USERNAME: ' . config('mail.mailers.smtp.username') . PHP_EOL;
echo 'MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption') . PHP_EOL;
echo 'MAIL_FROM_ADDRESS: ' . config('mail.from.address') . PHP_EOL;
echo 'MAIL_FROM_NAME: ' . config('mail.from.name') . PHP_EOL;
\$password = config('mail.mailers.smtp.password');
echo 'MAIL_PASSWORD: ' . (\$password ? '✅ Set (' . strlen(\$password) . ' characters)' : '❌ Not set') . PHP_EOL;
"

echo ""

# Step 4: Clear caches
echo "🧹 Step 4: Clearing Laravel Caches"
echo "----------------------------------"

php artisan config:clear
echo "✅ Config cache cleared"

php artisan cache:clear
echo "✅ Application cache cleared"

php artisan route:clear
echo "✅ Route cache cleared"

php artisan view:clear
echo "✅ View cache cleared"

# Rebuild config cache
php artisan config:cache
echo "✅ Config cache rebuilt"

echo ""

# Step 5: Test email sending
echo "📤 Step 5: Testing Email Functionality"
echo "--------------------------------------"

read -p "Enter email address to test with: " test_email

if [ -z "$test_email" ]; then
    test_email="rameshnda09@gmail.com"
    echo "Using default: $test_email"
fi

echo ""
echo "🧪 Running email debug test..."
php artisan debug:email "$test_email"

echo ""
echo "📊 Testing stock report email..."
php artisan debug:email "$test_email" --test-stock-report

echo ""

# Step 6: Provide recommendations
echo "💡 Step 6: Recommendations"
echo "--------------------------"

echo "If emails are still not working, try these solutions:"
echo ""
echo "1. 🔐 Gmail App Password Issues:"
echo "   - Ensure 2FA is enabled on Gmail account"
echo "   - Generate a new 16-character app password"
echo "   - Update MAIL_PASSWORD in .env file"
echo ""
echo "2. 🌐 Network/Firewall Issues:"
echo "   - Contact your hosting provider about SMTP port access"
echo "   - Try alternative ports (465 with SSL instead of 587 with TLS)"
echo ""
echo "3. 📧 Alternative Email Configuration:"
echo "   - Consider using SendGrid, Mailgun, or other email services"
echo "   - Update .env with alternative SMTP settings"
echo ""
echo "4. 🔍 Debug Commands:"
echo "   - View Laravel logs: tail -f storage/logs/laravel.log"
echo "   - Test manual email: php artisan debug:email your@email.com"
echo "   - Check cron logs: tail -f /var/log/cron.log"
echo ""

# Step 7: Offer to update .env
echo "🔧 Step 7: Environment Configuration"
echo "------------------------------------"

read -p "Do you want to update email settings in .env? (y/n): " update_env

if [[ $update_env == [yY] || $update_env == [yY][eE][sS] ]]; then
    echo ""
    echo "Current email settings will be backed up to .env.backup"
    cp .env .env.backup
    
    echo ""
    echo "Choose email configuration:"
    echo "1. Gmail SMTP (Port 587, TLS)"
    echo "2. Gmail SMTP (Port 465, SSL)"
    echo "3. Custom SMTP settings"
    
    read -p "Choose option (1-3): " email_option
    
    case $email_option in
        1)
            echo "Configuring Gmail SMTP (Port 587, TLS)..."
            sed -i 's/^MAIL_HOST=.*/MAIL_HOST=smtp.gmail.com/' .env
            sed -i 's/^MAIL_PORT=.*/MAIL_PORT=587/' .env
            sed -i 's/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=tls/' .env
            ;;
        2)
            echo "Configuring Gmail SMTP (Port 465, SSL)..."
            sed -i 's/^MAIL_HOST=.*/MAIL_HOST=smtp.gmail.com/' .env
            sed -i 's/^MAIL_PORT=.*/MAIL_PORT=465/' .env
            sed -i 's/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=ssl/' .env
            ;;
        3)
            read -p "Enter SMTP host: " smtp_host
            read -p "Enter SMTP port: " smtp_port
            read -p "Enter encryption (tls/ssl): " smtp_encryption
            
            sed -i "s/^MAIL_HOST=.*/MAIL_HOST=$smtp_host/" .env
            sed -i "s/^MAIL_PORT=.*/MAIL_PORT=$smtp_port/" .env
            sed -i "s/^MAIL_ENCRYPTION=.*/MAIL_ENCRYPTION=$smtp_encryption/" .env
            ;;
    esac
    
    # Clear and rebuild config cache
    php artisan config:clear
    php artisan config:cache
    
    echo "✅ Email configuration updated!"
    echo "🧪 Test the new configuration:"
    echo "php artisan debug:email $test_email"
fi

echo ""
echo "✨ Troubleshooting complete!"
echo ""
echo "📋 Next Steps:"
echo "1. Test email functionality: php artisan debug:email your@email.com"
echo "2. Set up cron job: ./setup-stock-alert-cron.sh"
echo "3. Monitor logs: tail -f storage/logs/laravel.log"