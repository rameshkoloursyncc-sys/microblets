#!/bin/bash

# Quick setup for 5 PM daily stock alert cron job

echo "🔧 Setting up Daily 5 PM Stock Alert"
echo "===================================="

# Get current directory
PROJECT_PATH=$(pwd)
echo "📁 Project Path: $PROJECT_PATH"

# Create log directory if it doesn't exist
sudo mkdir -p /var/log/microbelts
sudo chown $(whoami):$(whoami) /var/log/microbelts

# Set up the cron job for 5 PM daily
CRON_SCHEDULE="0 17 * * *"
DESCRIPTION="Daily at 5:00 PM IST"

# Create the cron command
CRON_COMMAND="$CRON_SCHEDULE cd $PROJECT_PATH && php artisan report:low-stock --email=rameshnda09@gmail.com --email=ramesh.koloursyncc@gmail.com >> /var/log/microbelts/stock-alerts.log 2>&1"

echo ""
echo "📋 Cron Job Details:"
echo "Schedule: $DESCRIPTION"
echo "Command: $CRON_COMMAND"
echo ""
echo "📧 Email Recipients:"
echo "   - rameshnda09@gmail.com"
echo "   - ramesh.koloursyncc@gmail.com"

echo ""
read -p "Do you want to add this cron job? (y/n): " confirm

if [[ $confirm == [yY] || $confirm == [yY][eE][sS] ]]; then
    # Remove any existing stock alert cron jobs first
    crontab -l 2>/dev/null | grep -v "report:low-stock" | crontab -
    
    # Add the new cron job
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
    echo "   View logs: tail -f /var/log/microbelts/stock-alerts.log"
    
    echo ""
    echo "🔧 Management commands:"
    echo "   View cron jobs: crontab -l"
    echo "   Edit cron jobs: crontab -e"
    echo "   Remove all cron jobs: crontab -r"
    
    echo ""
    echo "✨ Daily 5 PM stock alerts are now active!"
    echo "📧 Reports will be sent automatically every day at 5:00 PM IST"
    echo "🟡 Items will show YELLOW color after alerts are sent"
    
else
    echo "❌ Cron job setup cancelled."
fi

echo ""
echo "✨ Setup complete!"