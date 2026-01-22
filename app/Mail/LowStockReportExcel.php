<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Services\ExcelExportService;

class LowStockReportExcel extends Mailable
{
    use Queueable, SerializesModels;

    public $lowStockData;
    public $reportDate;
    public $excelFilePath;
    public $excelFileName;

    /**
     * Create a new message instance.
     */
    public function __construct($lowStockData)
    {
        $this->lowStockData = $lowStockData;
        $this->reportDate = now()->format('Y-m-d');
        
        // Generate Excel file
        $excelService = new ExcelExportService();
        $fileInfo = $excelService->generateStockAlertFile($lowStockData);
        
        $this->excelFilePath = $fileInfo['path'];
        $this->excelFileName = $fileInfo['filename'];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $lowStockCount = $this->lowStockData['total_low_stock_count'] ?? 0;
        $outOfStockCount = $this->lowStockData['total_out_of_stock_count'] ?? 0;
        
        return new Envelope(
            subject: "📊 Daily Stock Alert - {$lowStockCount} Low Stock, {$outOfStockCount} Out of Stock - {$this->reportDate}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock-report-excel',
            with: [
                'lowStockData' => $this->lowStockData,
                'reportDate' => $this->reportDate,
                'excelFileName' => $this->excelFileName
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->excelFilePath)
                ->as($this->excelFileName)
                ->withMime('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ];
    }
    
    /**
     * Clean up temporary files after sending
     */
    public function __destruct()
    {
        if (file_exists($this->excelFilePath)) {
            unlink($this->excelFilePath);
        }
    }
}