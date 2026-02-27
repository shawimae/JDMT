<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'message' => 'Method not allowed']);
  exit;
}

// ✅ Form fields
$name         = trim($_POST['name'] ?? '');
$contact      = trim($_POST['phone'] ?? '');     // from your HTML: name="phone"
$clientEmail  = trim($_POST['email'] ?? '');
$serviceType  = trim($_POST['service'] ?? '');
$issueDetails = trim($_POST['message'] ?? '');

if ($name === '' || $contact === '' || $clientEmail === '' || $serviceType === '' || $issueDetails === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'message' => 'Please complete all required fields.']);
  exit;
}

if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'message' => 'Invalid email address.']);
  exit;
}

// Basic anti-header injection (extra safety)
foreach ([$name, $clientEmail, $serviceType] as $v) {
  if (preg_match("/\r|\n/", $v)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid input.']);
    exit;
  }
}

// ✅ 1) SAVE TO DATABASE
require __DIR__ . '/config.php';

try {
  // If email_id is AUTO_INCREMENT, do NOT include it in insert
$stmt = $pdo->prepare("
  INSERT INTO email (name, email, contact, service_type, issue_details)
  VALUES (:name, :email, :contact, :service_type, :issue_details)
");

  $stmt->execute([
    ':name' => $name,
    ':email' => $clientEmail,
    ':contact' => $contact,
    ':service_type' => $serviceType,
    ':issue_details' => $issueDetails,
  ]);
  
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Failed to save request to database.']);
  exit;
}

// ✅ 2) SEND EMAIL VIA PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
  // =========================
  // SMTP SETTINGS (GMAIL EXAMPLE)
  // =========================
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;

  // 🔁 CHANGE THESE:
  $smtpUser = 'umayamshairamae.s@gmail.com';
  $smtpPass = 'rbhm jflr crfd dnzd';
  $toEmail  = 'umayamshairamae.s@gmail.com';

  $mail->Username   = $smtpUser;
  $mail->Password   = $smtpPass;

  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  $mail->CharSet = 'UTF-8';

  // Email headers
  $mail->setFrom($smtpUser, 'JDMT Website');
  $mail->addAddress($toEmail, 'JDMT Support');
  $mail->addReplyTo($clientEmail, $name);

  // Subject + Body
  $mail->isHTML(true);
  $mail->Subject = "JDMT Support Request — {$serviceType} — {$name}";

  $safeIssue = nl2br(htmlspecialchars($issueDetails, ENT_QUOTES, 'UTF-8'));

  $mail->Body = "
    <div style='font-family:Arial,sans-serif;line-height:1.6;color:#0b1220'>
      <h2 style='margin:0 0 10px'>New Support / Quote Request</h2>
      <table cellpadding='8' cellspacing='0' style='border-collapse:collapse;width:100%;max-width:720px'>
        <tr><td style='font-weight:700;border:1px solid #e6e8ee;width:160px'>Full Name</td><td style='border:1px solid #e6e8ee'>{$name}</td></tr>
        <tr><td style='font-weight:700;border:1px solid #e6e8ee'>Client Email</td><td style='border:1px solid #e6e8ee'>{$clientEmail}</td></tr>
        <tr><td style='font-weight:700;border:1px solid #e6e8ee'>Contact</td><td style='border:1px solid #e6e8ee'>{$contact}</td></tr>
        <tr><td style='font-weight:700;border:1px solid #e6e8ee'>Service Type</td><td style='border:1px solid #e6e8ee'>{$serviceType}</td></tr>
      </table>

      <h3 style='margin:18px 0 8px'>Issue Details</h3>
      <div style='border:1px solid #e6e8ee;padding:12px;border-radius:12px;background:#fafbff'>
        {$safeIssue}
      </div>
    </div>
  ";

  $mail->AltBody =
    "New JDMT Support/Quote Request\n\n" .
    "Name: {$name}\nEmail: {$clientEmail}\nContact: {$contact}\nService: {$serviceType}\n\nIssue:\n{$issueDetails}\n";

  $mail->send();

  echo json_encode(['ok' => true, 'message' => 'Request submitted successfully. Saved + emailed.']);

} catch (Exception $e) {
  // Note: saved in DB already, but email failed
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Saved to database, but email failed to send.',
    'error' => $mail->ErrorInfo
  ]);
}