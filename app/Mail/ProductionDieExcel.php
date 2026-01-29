<?php
namespace App\Mail;
use Illuminate\Mail\Mailable;
use App\Services\ExcelExportService;

class ProductionPlanningExcel extends Mailable
{
    public $planningData;
    
    public function __construct($planningData)
    {
        $this->planningData = $planningData;
    }
    
    public function build()
    {
        // Generate production planning Excel (SIZE, MAKE, PARTY only)
        $excelService = new ExcelExportService();
        $spreadsheet = $excelService->generateProductionPlanningOnlyExcel($this->planningData);
        $filePath = $excelService->saveToTempFile($spreadsheet, 'production_planning_' . date('Y-m-d') . '.xlsx');
        
        return $this->subject('Production Planning Summary - ' . date('d M Y'))
                    ->view('emails.production-planning-excel')
                    ->attach($filePath, ['as' => 'Production_Planning_Summary.xlsx'])
                    ->with(['planningData' => $this->planningData]);
    }
}
