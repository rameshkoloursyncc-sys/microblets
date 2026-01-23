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

class SmartStockReportExcel extends Mailable
{
    use Queueable, SerializesModels;

    public $alertData;
    public $excelFilePath;
    public $excelFileName;

    /**
     * Create a new message instance.
     */
    public function __construct($alertData)
    {
        $this->alertData = $alertData;
        
        // Generate Excel file
        $excelService = new ExcelExportService();
        $fileInfo = $excelService->generateSmartStockAlertFile($alertData);
        
        $this->excelFilePath = $fileInfo['path'];
        $this->excelFileName = $fileInfo['filename'];
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $totalItems = $this->alertData['total_items'] ?? 0;
        $totalDies = $this->alertData['total_dies_needed'] ?? 0;
        
        return new Envelope(
            subject: "Microbelts Daily Stock Report - {$totalItems} Items Need {$totalDies} Dies",
        );
    }   

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.smart-stock-report-excel',
            with: [
                'alertData' => $this->alertData,
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