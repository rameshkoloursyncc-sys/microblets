<!DOCTYPE html>
<html>
<head><title>Production Planning</title></head>
<body>
    <h2>Daily Production Die Summary</h2>
    <p><strong>Generated At:</strong> {{ \Carbon\Carbon::parse($planningData['generated_at'] ?? now())->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</p>
    
    <p>Total Dies Required: <strong>{{ $planningData['total_dies_needed'] ?? 0 }}</strong></p>
    
    <p>Please find the production planning details in the attached Excel file.</p>
    
    <p>Microbelts Inventory System</p>
</body>
</html>
