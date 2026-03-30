<?php
// certificate.php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Get profile ID from URL
$id = isset($_GET['id']) ? $_GET['id'] : 0;

// Get barangay parameter - THIS IS THE KEY
$barangay = isset($_GET['barangay']) ? $_GET['barangay'] : '';

if ($id == 0) {
    header("Location: barangays.php");
    exit();
}

// Fetch profile data
$query = "SELECT * FROM biographical_profiles WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: barangays.php");
    exit();
}

$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current date
$currentDate = date('F d, Y');
$generatedBy = $_SESSION['full_name'] . ' - ' . $_SESSION['rank'];

// Certificate number
$certNumber = 'PNP-MFPS-CERT-' . str_pad($id, 5, '0', STR_PAD_LEFT) . '-' . date('Y');

// Determine back link - ALWAYS go back to the barangay page if barangay exists
if (!empty($barangay)) {
    $back_link = "barangay_profiles.php?barangay=" . urlencode($barangay);
    $back_text = "Back to " . htmlspecialchars($barangay);
} else {
    // If no barangay is provided, try to get it from profile address
    $addressParts = explode(',', $profile['present_address']);
    $profileBarangay = trim($addressParts[0]);
    if (!empty($profileBarangay)) {
        $back_link = "barangay_profiles.php?barangay=" . urlencode($profileBarangay);
        $back_text = "Back to " . htmlspecialchars($profileBarangay);
    } else {
        $back_link = "barangays.php";
        $back_text = "Back to Barangays";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of Completion - PNP Biographical Profiling System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f4f7fb;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .certificate-container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .certificate {
            background: white;
            border: 15px solid #0a2f4d;
            padding: 25px 60px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            min-height: 650px;
            display: flex;
            flex-direction: column;
        }

        .certificate::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 8px;
            right: 8px;
            bottom: 8px;
            border: 2px solid #c9a959;
            pointer-events: none;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .pnp-logo {
            width: 80px;
            height: 80px;
            background: #0a2f4d;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 8px;
            border: 3px solid #c9a959;
        }

        .pnp-logo i {
            font-size: 40px;
            color: #c9a959;
        }

        .dilg {
            font-size: 12px;
            color: #64748b;
            letter-spacing: 1px;
        }

        .pnp {
            font-size: 24px;
            font-weight: 700;
            color: #0a2f4d;
            margin: 2px 0;
        }

        .station {
            font-size: 18px;
            color: #c9a959;
            font-weight: 600;
            margin: 2px 0;
        }

        .certificate-title {
            text-align: center;
            margin: 10px 0;
        }

        .certificate-title h1 {
            font-size: 42px;
            color: #0a2f4d;
            font-weight: 700;
            letter-spacing: 4px;
            margin-bottom: 3px;
        }

        .certificate-title h2 {
            font-size: 24px;
            color: #c9a959;
            font-weight: 500;
            letter-spacing: 2px;
        }

        .content {
            flex: 1;
            margin: 5px 0;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .content p {
            font-size: 16px;
            color: #1e293b;
            line-height: 1.8;
            margin: 2px 0;
        }

        .recipient-name {
            font-size: 36px;
            font-weight: 700;
            color: #0a2f4d;
            margin: 10px 0;
            padding: 5px 30px;
            border-bottom: 3px solid #c9a959;
            display: inline-block;
            align-self: center;
        }

        .signature {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #c9a959;
        }

        .signature-line {
            text-align: center;
            width: 280px;
        }

        .signature-line p {
            margin: 3px 0;
        }

        .signature-name {
            font-weight: 700;
            color: #0a2f4d;
            margin-top: 20px;
            font-size: 14px;
        }

        .signature-title {
            color: #64748b;
            font-size: 12px;
        }

        .certificate-footer {
            margin-top: 5px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 15px;
        }

        .certificate-footer p {
            margin: 2px 0;
        }

        .certificate-number {
            font-size: 11px;
            color: #94a3b8;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 30px;
            display: inline-block;
        }

        .action-buttons {
            text-align: center;
            margin-top: 20px;
        }

        .btn-print {
            background: #0a2f4d;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin: 0 5px;
        }

        .btn-print:hover {
            background: #c9a959;
            color: #0a2f4d;
        }

        .btn-back {
            background: #64748b;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin: 0 5px;
        }

        .btn-back:hover {
            background: #475569;
        }

        /* Print Styles - ONE PAGE LANDSCAPE */
        @media print {
            @page {
                size: landscape;
                margin: 0.3in;
            }

            html, body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            body {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            
            .certificate-container {
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            .certificate {
                border: 12px solid #0a2f4d;
                box-shadow: none;
                min-height: 0;
                padding: 15px 40px;
                margin: 0;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            
            .pnp-logo {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: #0a2f4d !important;
                width: 70px;
                height: 70px;
            }
            
            .pnp-logo i {
                color: #c9a959 !important;
                font-size: 35px;
            }
            
            .certificate::before {
                border: 2px solid #c9a959;
            }

            /* Compact sizes for printing */
            .pnp {
                font-size: 22px;
            }
            
            .station {
                font-size: 16px;
            }
            
            .certificate-title h1 {
                font-size: 36px;
            }
            
            .certificate-title h2 {
                font-size: 20px;
            }
            
            .recipient-name {
                font-size: 32px;
                margin: 8px 0;
                padding: 5px 25px;
            }
            
            .content p {
                font-size: 14px;
                line-height: 1.6;
                margin: 1px 0;
            }
            
            .signature {
                margin-top: 10px;
                padding-top: 8px;
            }
            
            .signature-name {
                margin-top: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate">
            <!-- PNP Logo -->
            <div class="header">
                <div class="pnp-logo">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="dilg">Department of the Interior and Local Government</div>
                <div class="pnp">PHILIPPINE NATIONAL POLICE</div>
                <div class="station">MANOLO FORTICH POLICE STATION</div>
                <div style="font-size: 13px; color: #64748b; margin-top: 3px;">Bukidnon Police Provincial Office</div>
            </div>

            <!-- Certificate Title -->
            <div class="certificate-title">
                <h1>CERTIFICATE</h1>
                <h2>OF COMPLETION</h2>
            </div>

            <!-- Content -->
            <div class="content">
                <p>This is to certify that</p>
                <div class="recipient-name"><?php echo strtoupper(htmlspecialchars($profile['full_name'])); ?></div>
                <p>has successfully completed the biographical profiling process</p>
                <p>and is hereby recognized for their cooperation and compliance</p>
                <p>with the requirements of this office.</p>

                <p style="font-style: italic; color: #64748b; margin-top: 15px;">"In the service of God, Country, and People"</p>
            </div>

            <!-- Signatures -->
            <div class="signature">
                <div class="signature-line">
                    <p>_________________________</p>
                    <p class="signature-name">P/CHIEF OF POLICE</p>
                    <p class="signature-title">Chief of Police</p>
                    <p class="signature-title">Manolo Fortich Police Station</p>
                </div>
                <div class="signature-line">
                    <p>_________________________</p>
                    <p class="signature-name"><?php echo $_SESSION['full_name']; ?></p>
                    <p class="signature-title"><?php echo $_SESSION['rank']; ?></p>
                    <p class="signature-title">Intelligence Officer</p>
                </div>
            </div>

            <!-- Footer -->
            <div class="certificate-footer">
                <p><i class="fas fa-map-marker-alt"></i> Manolo Fortich, Bukidnon, Philippines 8703</p>
                <p><i class="fas fa-phone"></i> (088-228) 2244</p>
                <span class="certificate-number">
                    <i class="fas fa-certificate"></i> <?php echo $certNumber; ?>
                </span>
                <p>Issued on: <?php echo $currentDate; ?></p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Print Certificate
            </button>
            <a href="<?php echo $back_link; ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> <?php echo $back_text; ?>
            </a>
        </div>
    </div>

    <script>
        // Ensure single page print
        document.querySelector('.btn-print').addEventListener('click', function() {
            window.print();
        });
    </script>
</body>
</html>