<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockReport;

class DebugEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:email {email} {--test-stock-report : Send a test stock report instead of simple email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug email sending with detailed output and configuration check';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $testStockReport = $this->option('test-stock-report');
        
        $this->info('🔍 Email Configuration Debug');
        $this->info('================================');
        
        // Display current configuration
        $this->displayConfiguration();
        
        // Test SMTP connectivity
        $this->testSmtpConnectivity();
        
        // Send test email
        if ($testStockReport) {
            $this->sendTestStockReport($email);
        } else {
            $this->sendSimpleTestEmail($email);
        }
    }
    
    private function displayConfiguration()
    {
        $this->info('📧 Current Mail Configuration:');
        $this->line('MAIL_MAILER: ' . config('mail.default'));
        $this->line('MAIL_HOST: ' . config('mail.mailers.smtp.host'));
        $this->line('MAIL_PORT: ' . config('mail.mailers.smtp.port'));
        $this->line('MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->line('MAIL_ENCRYPTION: ' . config('mail.mailers.smtp.encryption'));
        $this->line('MAIL_FROM_ADDRESS: ' . config('mail.from.address'));
        $this->line('MAIL_FROM_NAME: ' . config('mail.from.name'));
        $this->line('QUEUE_CONNECTION: ' . config('queue.default'));
        $this->newLine();
        
        // Check if password is set (without revealing it)
        $password = config('mail.mailers.smtp.password');
        $this->line('MAIL_PASSWORD: ' . ($password ? '✅ Set (' . strlen($password) . ' characters)' : '❌ Not set'));
        $this->newLine();
    }
    
    private function testSmtpConnectivity()
    {
        $this->info('🌐 Testing SMTP Connectivity:');
        
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        
        if ($connection) {
            $this->line("✅ Successfully connected to {$host}:{$port}");
            fclose($connection);
        } else {
            $this->error("❌ Failed to connect to {$host}:{$port}");
            $this->error("Error: {$errstr} (Code: {$errno})");
        }
        $this->newLine();
    }
    
    private function sendSimpleTestEmail($email)
    {
        $this->info('📤 Sending Simple Test Email:');
        
        try {
            $testMessage = "Production Email Debug Test\n\n";
            $testMessage .= "Timestamp: " . now()->toDateTimeString() . "\n";
            $testMessage .= "Server: " . gethostname() . "\n";
            $testMessage .= "Environment: " . app()->environment() . "\n";
            $testMessage .= "Laravel Version: " . app()->version() . "\n";
            $testMessage .= "PHP Version: " . phpversion() . "\n\n";
            $testMessage .= "If you receive this email, the basic email configuration is working correctly.";
            
            Mail::raw($testMessage, function($message) use ($email) {
                $message->to($email)
                        ->subject('🔧 Production Email Debug Test - ' . now()->format('Y-m-d H:i:s'));
            });
            
            $this->info("✅ Simple test email sent successfully to: {$email}");
            $this->info("📬 Please check your inbox (and spam folder)");
            
        } catch (\Exception $e) {
            $this->error('❌ Simple test email failed:');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            // Additional debugging info
            if (str_contains($e->getMessage(), 'Connection could not be established')) {
                $this->warn('💡 This looks like a connectivity issue. Check:');
                $this->warn('   - Firewall settings');
                $this->warn('   - SMTP port availability');
                $this->warn('   - Network connectivity');
            } elseif (str_contains($e->getMessage(), 'authentication')) {
                $this->warn('💡 This looks like an authentication issue. Check:');
                $this->warn('   - Gmail app password is correct');
                $this->warn('   - 2FA is enabled on Gmail account');
                $this->warn('   - Username/password in .env file');
            }
        }
        $this->newLine();
    }
    
    private function sendTestStockReport($email)
    {
        $this->info('📊 Sending Test Stock Report Email:');
        
        try {
            // Create sample stock report data
            $testData = [
                'low_stock_items' => [
                    'vee_belts' => [
                        'name' => 'Vee Belts',
                        'items' => [
                            (object)[
                                'id' => 1,
                                'section' => 'A',
                                'size' => '100',
                                'current_stock' => 5,
                                'reorder_level' => 10,
                                'value' => 500
                            ]
                        ],
                        'count' => 1
                    ]
                ],
                'out_of_stock_items' => [
                    'timing_belts' => [
                        'name' => 'Timing Belts',
                        'items' => [
                            (object)[
                                'id' => 2,
                                'section' => 'XL',
                                'size' => '120',
                                'current_stock' => 0,
                                'reorder_level' => 5,
                                'value' => 0
                            ]
                        ],
                        'count' => 1
                    ]
                ],
                'total_low_stock_count' => 1,
                'total_out_of_stock_count' => 1,
                'total_alert_count' => 2,
                'generated_at' => now()->toDateTimeString()
            ];
            
            Mail::to($email)->send(new LowStockReport($testData));
            
            $this->info("✅ Test stock report email sent successfully to: {$email}");
            $this->info("📬 Please check your inbox for the stock report email");
            
        } catch (\Exception $e) {
            $this->error('❌ Test stock report email failed:');
            $this->error('Error: ' . $e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }
}