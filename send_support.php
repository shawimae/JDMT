<?php
// send_support.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("Location: index.php");
  exit;
}

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $email === '' || $service === '' || $message === '') {
  die("Missing required fields.");
}

$mail = new PHPMailer(true);

try {
  // ✅ DEBUG (remove after testing)
  $mail->SMTPDebug  = 2;
  $mail->Debugoutput = 'html';

  // ✅ SMTP Settings (Gmail)
  $mail->isSMTP();
  $mail->Host       = 'smtp.gmail.com';
  $mail->SMTPAuth   = true;

  // MUST be full Gmail address
  $mail->Username   = 'umayamshairamae.s@gmail.com';

  // MUST be Gmail APP PASSWORD (16 characters)
  // Example format: abcd efgh ijkl mnop (or without spaces)
  $mail->Password   = 'PASTE_YOUR_REAL_APP_PASSWORD_HERE';

  $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  $mail->Port       = 587;

  // ✅ Sender/Receiver
  $mail->setFrom('umayamshairamae.s@gmail.com', 'JDMT Website');
  $mail->addAddress('umayamshairamae.s@gmail.com', 'JDMT Support Inbox');

  // ✅ Reply-to should be the customer (optional but nice)
  $mail->addReplyTo($email, $name);

  // ✅ Email content
  $mail->isHTML(true);
  $mail->Subject = "New Support Request - {$service}";
  $mail->Body    = "
    <h2>New Support Request</h2>
    <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
    <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
    <p><strong>Service:</strong> " . htmlspecialchars($service) . "</p>
    <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
  ";

  $mail->AltBody =
    "New Support Request\n\n" .
    "Name: $name\n" .
    "Phone: $phone\n" .
    "Email: $email\n" .
    "Service: $service\n\n" .
    "Message:\n$message\n";

  $mail->send();

  header("Location: index.php?sent=1#contact");
  exit;

} catch (Exception $e) {
  http_response_code(500);
  echo "Mailer Error: " . $mail->ErrorInfo;
}