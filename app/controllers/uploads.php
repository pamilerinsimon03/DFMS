<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../services/FilenameGenerator.php';
require_once __DIR__ . '/../services/QualityCheckService.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /public/index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user = User::getById($user_id);
$tab = isset($_GET['tab']) ? strtolower($_GET['tab']) : 'total';

// Handle re-upload logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reupload_id'])) {
    // Validate CSRF token
    requireCSRFToken();
    
    $doc_id = $_POST['reupload_id'];
    $file = $_FILES['file'];
    $original_doc = Document::getById($doc_id);

    if ($original_doc && $original_doc['user_id'] == $user_id) {
        // --- Quality Validation ---
        $filePath = $file['tmp_name'];
        $debug_messages = [];
        $isValid = true;
        // define('MIN_BLURRINESS', 15.0);
        // define('MIN_OCR_CONFIDENCE', 60);

        
        // if (QualityCheckService::isImage($filePath)) {
        //     $blurriness = QualityCheckService::getBlurriness($filePath, $debug_messages);
        //     if ($blurriness < MIN_BLURRINESS) {
        //         $_SESSION['upload_message'] = "Re-upload failed: The image is too blurry.";
        //         $isValid = false;
        //     }
        // } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'pdf') {
        //     $ocrConfidence = QualityCheckService::getOcrConfidence($filePath, $debug_messages);
        //     if ($ocrConfidence < MIN_OCR_CONFIDENCE) {
        //         $_SESSION['upload_message'] = "Re-upload failed: PDF text has low confidence.";
        //         $isValid = false;
        //     }
        // }
        // QualityCheckService::logDebugMessages($user_id, $debug_messages);

        if (true) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $generated_filename = FilenameGenerator::generate($user_id, $original_doc['document_name'], $original_doc['semester'], $extension);

            $result = Document::reupload($doc_id, $file, $generated_filename);
            $_SESSION['upload_message'] = $result['message'];
        }
    } else {
        $_SESSION['upload_message'] = 'Invalid document or permission denied.';
    }

    header("Location: /app/controllers/uploads.php?tab=" . urlencode($tab));
    exit();
}

// Fetch documents for the view
$search = $_GET['search'] ?? null;
if ($tab === 'total') {
    $documents = Document::getByUserId($user_id, $search);
} else {
    $documents = Document::getByUserIdAndStatus($user_id, $tab);
    if ($search) {
        $documents = array_filter($documents, function($doc) use ($search) {
            return stripos($doc['document_name'], $search) !== false;
        });
    }
}

// Notifications for header dropdown
$notifications = Notification::getForUser($user_id, 15);
$unread_count = Notification::countUnread($user_id);

// Prepare view variables
$page_title = 'My Uploads';
$active_page = 'uploads';
$css_files = ['uploads.css'];
$js_files = ['uploads.js', 'dashboard.js'];

// SPA Request Handling
if (isset($_SERVER['HTTP_X_SPA_REQUEST'])) {
    ob_start();
    require_once __DIR__ . '/../views/uploads.php';
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'html' => $html, 
        'title' => $page_title,
        'css_files' => $css_files,
        'js_files' => $js_files
    ]);
    exit();
}

// --- AJAX Response Handling ---
if (isset($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
    ob_start();
    if (empty($documents)): ?>
        <tr class="table-row"><td colspan="6" class="td-sn">No documents found.</td></tr>
    <?php else: 
        foreach ($documents as $index => $doc): ?>
            <tr class="table-row">
                <td class="td-sn" data-label="SN"><span class="cell-value"><?php echo $index + 1; ?>.</span></td>
                <td class="td-name" data-label="Name"><span class="cell-value"><?php echo htmlspecialchars($doc['document_name']); ?></span></td>
                <td class="td-document" data-label="Document"><span class="cell-value truncate"><?php echo htmlspecialchars(basename($doc['file_path'])); ?></span></td>
                <td class="td-date" data-label="Date"><span class="cell-value"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></span></td>
                <td class="td-status <?php echo strtolower(str_replace(' ', '-', $doc['status'])); ?>" data-label="Status">
                    <span class="status-wrapper">
                        <span class="status-dot"></span>
                        <span class="status-text"><?php echo htmlspecialchars($doc['status']); ?></span>
                    </span>
                </td>
                <td class="td-action" data-label="Action">
                    <?php if ($doc['status'] === 'Rejected'): ?>
                    <form action="/app/controllers/uploads.php?tab=<?php echo htmlspecialchars($tab); ?>" method="POST"
                        enctype="multipart/form-data" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" name="reupload_id" value="<?php echo $doc['id']; ?>">
                        <input type="file" id="reupload-<?php echo $doc['id']; ?>" name="file"
                            accept=".pdf,.jpg,.jpeg,.png" required style="display: none;" onchange="this.form.submit()">
                        <label for="reupload-<?php echo $doc['id']; ?>" class="btn-danger">
                            Reupload Document
                        </label>
                    </form>
                    <?php elseif ($doc['status'] === 'Approved'): ?>
                    <button class="btn-success" disabled>Successful</button>
                    <?php else: ?>
                    <button class="btn-warning" disabled>
                        Awaiting Verification
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
       <?php endforeach;
    endif;
    $html = ob_get_clean();

    // Generate Info HTML
    ob_start(); // Placeholder for info if needed, or return empty?
    // Verifications has search info, let's add it if search is active.
    if (!empty($search)): ?>
         <div class="search-results-info">
            <span class="results-count"><?php echo count($documents); ?> result<?php echo count($documents) !== 1 ? 's' : ''; ?> found</span>
            <span class="search-term">for "<?php echo htmlspecialchars($search); ?>"</span>
            <a href="?tab=<?php echo urlencode($tab); ?>" class="clear-search">Clear search</a>
        </div>
    <?php endif;
    $info_html = ob_get_clean();

    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'info_html' => $info_html]);
    exit();
}

require_once __DIR__ . '/../views/partials/header.php';
require_once __DIR__ . '/../views/partials/sidebar.php';
require_once __DIR__ . '/../views/uploads.php';
require_once __DIR__ . '/../views/partials/footer.php';