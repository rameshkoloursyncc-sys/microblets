#!/bin/bash

# Stock Alert Cron Job Setup Script
# This script helps you set up automated stock alert emails

echo "🔧 Microbelts Stock Alert Cron Job Setup"
echo "========================================"

# Get current directory
PROJECT_PATH=$(pwd)
echo "📁 Project Path: $PROJECT_PATH"

# Create log directory if it doesn't exist
sudo mkdir -p /var/log/microbelts
sudo chown $(whoami):$(whoami) /var/log/microbelts

echo ""
echo "📧 Email Recipients:"
echo "   - rameshnda09@gmail.com"
echo "   - ramesh.koloursyncc@gmail.com"

echo ""
echo "⏰ Available Cron Schedule Options:"
echo "1. Daily at 8:00 AM IST (Current Laravel scheduler)"
echo "2. Twice daily (8:00 AM and 6:00 PM IST)"
echo "3. Every 6 hours"
echo "4. Every weekday at 8:00 AM IST"
echo "5. Custom schedule"
echo "6. Test email now (no cron setup)"

echo ""
read -p "Choose an option (1-6): " choice

case $choice in
    1)
        CRON_SCHEDULE="0 8 * * *"
        DESCRIPTION="Daily at 8:00 AM IST"
        ;;
    2)
        CRON_SCHEDULE="0 8,18 * * *"
        DESCRIPTION="Twice daily (8:00 AM and 6:00 PM IST)"
        ;;
    3)
        CRON_SCHEDULE="0 */6 * * *"
        DESCRIPTION="Every 6 hours"
        ;;
    4)
        CRON_SCHEDULE="0 8 * * 1-5"
        DESCRIPTION="Every weekday at 8:00 AM IST"
        ;;
    5)
        echo ""
        echo "📝 Cron Schedule Format: minute hour day month weekday"
        echo "Examples:"
        echo "  0 8 * * *     = Daily at 8:00 AM"
        echo "  0 */4 * * *   = Every 4 hours"
        echo "  0 9 * * 1     = Every Monday at 9:00 AM"
        echo ""
        read -p "Enter custom cron schedule: " CRON_SCHEDULE
        DESCRIPTION="Custom schedule: $CRON_SCHEDULE"
        ;;
    6)
        echo ""
        echo "🧪 Testing email functionality..."
        php artisan debug:email rameshnda09@gmail.com
        echo ""
        echo "📊 Testing stock report email..."
        php artisan debug:email rameshnda09@gmail.com --test-stock-report
        exit 0
        ;;
    *)
        echo "❌ Invalid option. Exiting."
        exit 1
        ;;
esac

# Create the cron command
CRON_COMMAND="$CRON_SCHEDULE cd $PROJECT_PATH && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/microbelts/stock-alerts.log 2>&1"

echo ""
echo "📋 Cron Job Details:"
echo "Schedule: $DESCRIPTION"
echo "Command: $CRON_COMMAND"

echo ""
read -p "Do you want to add this cron job? (y/n): " confirm

if [[ $confirm == [yY] || $confirm == [yY][eE][sS] ]]; then
    # Add the cron job
    (crontab -l 2>/dev/null; echo "$CRON_COMMAND") | crontab -
    
    echo ""
    echo "✅ Cron job added successfully!"
    echo ""
    echo "📋 Current cron jobs:"
    crontab -l
    
    echo ""
    echo "📝 Log files:"
    echo "   Stock alerts: /var/log/microbelts/stock-alerts.log"
    echo "   Laravel logs: $PROJECT_PATH/storage/logs/laravel.log"
    
    echo ""
    echo "🧪 Test commands:"
    echo "   Manual test: php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com"
    echo "   Debug email: php artisan debug:email rameshnda09@gmail.com"
    echo "   View logs: tail -f /var/log/microbelts/stock-alerts.log"
    
    echo ""
    echo "🔧 Management commands:"
    echo "   View cron jobs: crontab -l"
    echo "   Edit cron jobs: crontab -e"
    echo "   Remove all cron jobs: crontab -r"
    
else
    echo "❌ Cron job setup cancelled."
fi

echo ""
echo "✨ Setup complete!"