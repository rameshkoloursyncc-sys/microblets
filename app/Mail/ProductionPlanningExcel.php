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

class ProductionPlanningExcel extends Mailable
{
    use Queueable, SerializesModels;

    public $planningData;
    public $excelFilePath;
    public $excelFileName;

    /**
     * Create a new message instance.
     */
    public function __construct($planningData)
    {
        $this->planningData = $planningData;
        
        // Generate Excel file using production planning method
        $excelService = new ExcelExportService();
        $spreadsheet = $excelService->generateProductionPlanningOnlyExcel($planningData);
        
        // Save to temp file
        $this->excelFileName = 'production_planning_' . date('Y-m-d') . '.xlsx';
        $this->excelFilePath = $excelService->saveToTempFile($spreadsheet, $this->excelFileName);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $totalDies = $this->planningData['total_dies_needed'] ?? 0;
        
        return new Envelope(
            subject: "Microbelts Production Planning - {$totalDies} Dies Required - " . now()->format('d M Y'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.production-planning-excel',
            with: [
                'planningData' => $this->planningData,
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