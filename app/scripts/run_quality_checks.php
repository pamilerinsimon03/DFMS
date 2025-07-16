<?php
// Set a long execution time for this background script
set_time_limit(600); 

require_once __DIR__ . '/../config/config.php';

// Define constants for the quality checks
define('MIN_BLURRINESS', 15.0);
define('MIN_OCR_CONFIDENCE', 60);

// --- Function Definitions for Quality Checks ---

function isImage($filePath) {
    if (!file_exists($filePath) || !is_readable($filePath)) return false;
    $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
    return in_array($mime, ['image/jpeg','image/png','image/gif']);
}

function getBlurriness($filePath, array &$debug_messages) {
    if (!function_exists('imagecreatefromstring') || !function_exists('imageconvolution')) {
        $debug_messages[] = '[GD convolution unavailable]';
        return -1;
    }
    $data = @file_get_contents($filePath);
    $img  = @imagecreatefromstring($data);
    if (!$img) { $debug_messages[]='[GD failed to load image]'; return -2; }
    $maxDim = 500; $w=imagesx($img); $h=imagesy($img);
    if (max($w,$h) > $maxDim) {
        $scale = $maxDim/max($w,$h);
        $nw = (int)($w*$scale); $nh=(int)($h*$scale);
        $tmp = imagecreatetruecolor($nw,$nh);
        imagecopyresampled($tmp,$img,0,0,0,0,$nw,$nh,$w,$h);
        imagedestroy($img);
        $img=$tmp; $w=$nw; $h=$nh;
    }
    $kernel=[[-1,-1,-1],[-1,8,-1],[-1,-1,-1]];
    if (!imageconvolution($img,$kernel,1,0)) {
        imagedestroy($img); $debug_messages[]='[GD convolution failed]'; return -3;
    }
    $resp=[]; $step=max(1,floor(min($w,$h)/100));
    for($y=0;$y<$h;$y+=$step) for($x=0;$x<$w;$x+=$step){
        $rgb=imagecolorat($img,$x,$y);
        $r=($rgb>>16)&0xFF; $g=($rgb>>8)&0xFF; $b=$rgb&0xFF;
        $resp[]=($r+$g+$b)/3;
    }
    imagedestroy($img);
    if (empty($resp)){ $debug_messages[]='[No samples]'; return -4; }
    $n=count($resp);
    $mean=array_sum($resp)/$n;
    $sumSq=array_reduce($resp,fn($c,$v)=>$c+($v-$mean)**2,0);
    $std=sqrt($sumSq/$n);
    $debug_messages[] = "[Blur score: ".round($std,2)."]";
    return $std;
}

function getOcrConfidence($filePath, $tesseractPath, array &$debug_messages) {
    $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
    $raster = $filePath;
    if (stripos($mime,'pdf')!==false) {
        if (!extension_loaded('imagick')) { $debug_messages[]='[Imagick not loaded]'; return -1; }
        try {
            $im=new Imagick(); $im->setResolution(300,300);
            $im->readImage($filePath.'[0]');
            $im->setImageFormat('png');
            $raster = tempnam(sys_get_temp_dir(),'ocr_').'.png';
            $im->writeImage($raster);
            $im->clear(); $im->destroy();
            $debug_messages[] = "[Rasterized PDF → $raster]";
        } catch (Exception $e) {
            $debug_messages[] = "[Rasterization error: {$e->getMessage()}]";
            return -1;
        }
    }
    
    $base = tempnam(sys_get_temp_dir(),'ocr_'); unlink($base);
    $cmd  = sprintf('%s %s %s tsv -l eng --psm 3 2>&1', escapeshellarg($tesseractPath), escapeshellarg($raster), escapeshellarg($base));
    $debug_messages[] = "[OCR cmd: $cmd]";
    shell_exec($cmd);
    $tsv = "$base.tsv";
    @unlink($raster!=$filePath?$raster:'');
    if (!file_exists($tsv)||filesize($tsv)===0){ @unlink($tsv); $debug_messages[]='[No OCR output]'; return 0; }
    $lines=file($tsv,FILE_IGNORE_NEW_LINES);
    @unlink($tsv);
    $confs = array_map('intval', array_filter(array_map(fn($l)=>explode("	",$l)[10]??0,$lines)));
    if (empty($confs)) { $debug_messages[]='[No confs]'; return 0; }
    return array_sum($confs)/count($confs);
}

// --- Main Script Logic ---

global $conn;
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "
");
}

echo "Running Quality Check Processor...
";

// --- Check for Tesseract OCR dependency ---
$tessRaw = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? shell_exec('where tesseract') : shell_exec('which tesseract');
$tessPath = is_string($tessRaw) ? trim($tessRaw) : '';
if (empty($tessPath)) {
    die("FATAL ERROR: Tesseract OCR executable not found.
Please install Tesseract and ensure it is in your system's PATH.
On Windows, reinstall and ensure the 'Add to PATH' option is checked.
On Linux, use 'sudo apt-get install tesseract-ocr'.
");
}
echo "Found Tesseract OCR at: $tessPath
";

// Fetch documents awaiting review
$result = $conn->query("SELECT id, file_path FROM documents WHERE status = 'Pending Quality Check'");
if (!$result) {
    die("Error fetching documents: " . $conn->error . "
");
}

$documents = $result->fetch_all(MYSQLI_ASSOC);
$result->free();

if (empty($documents)) {
    echo "No documents are currently awaiting quality review.
";
    $conn->close();
    exit;
}

echo "Found " . count($documents) . " documents to process.
";

$updateStmt = $conn->prepare("UPDATE documents SET status = ?, rejection_reason = ? WHERE id = ?");

foreach ($documents as $doc) {
    $docId = $doc['id'];
    $filePath = __DIR__ . '/../../public/' . $doc['file_path'];
    $dbg = [];
    $status = 'Pending Approval';
    $rejection_reason = null;

    echo "Processing doc ID $docId: {$doc['file_path']}...
";

    if (!file_exists($filePath)) {
        $status = 'Rejected';
        $rejection_reason = 'File not found at path: ' . $doc['file_path'];
    } else {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($ext === 'pdf') {
            $confidence = getOcrConfidence($filePath, $tessPath, $dbg);
            if ($confidence < 0) {
                $status = 'Rejected';
                $rejection_reason = 'PDF is not readable or an OCR error occurred.';
            } elseif ($confidence < MIN_OCR_CONFIDENCE) {
                $status = 'Rejected';
                $rejection_reason = "Low text confidence (Score: " . round($confidence) . "%). Document may be unreadable.";
            }
        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $blurriness = getBlurriness($filePath, $dbg);
            if ($blurriness < 0) {
                $status = 'Rejected';
                $rejection_reason = 'Could not analyze image sharpness.';
            } elseif ($blurriness < MIN_BLURRINESS) {
                $status = 'Rejected';
                $rejection_reason = "Image is too blurry (Score: " . round($blurriness, 2) . "). Please upload a clearer image.";
            }
        }
    }
    
    $updateStmt->bind_param("ssi", $status, $rejection_reason, $docId);
    $updateStmt->execute();
    echo " -> Result: $status" . ($rejection_reason ? " ($rejection_reason)" : "") . "
";
}

$updateStmt->close();
$conn->close();

echo "Processing complete.
";