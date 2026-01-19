<?php
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }

    $isLocalhost = (
        strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
        strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
    );

    /* ---------------- LOCAL TEST MODE ---------------- */
    if ($isLocalhost && !getenv('GMAIL_SMTP_USER')) {

        $logFile = 'email_submissions.txt';
        $logContent  = "=== Form Submission at " . date('Y-m-d H:i:s') . " ===\n";
        $logContent .= "Name: $name\n";
        $logContent .= "Email: $email\n";
        $logContent .= "Phone: $phone\n";
        $logContent .= "Message: $message\n";
        $logContent .= "==========================================\n\n";

        if (@file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX)) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Form submitted successfully! (Local mode - saved to email_submissions.txt)'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error saving submission'
            ]);
        }
        exit;
    }

    /* ---------------- LIVE MODE (SMTP) ---------------- */
    try {
        require 'email_sending_vendor/autoload.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        // growingleaf.in SMTP SETTINGS
        $mail->isSMTP();
        $mail->Host = 'cp233.fra21.ulta22cp.comHostName';
        $mail->SMTPAuth = true;
        $mail->Username = 'info@growingleaf.in';
        $mail->Password = 'Password';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // // Gmail SMTP
        // $mail->isSMTP();
        // $mail->Host       = 'smtp.gmail.com';
        // $mail->SMTPAuth   = true;
        // $mail->Username   = 'sinovthomas@gmail.com';
        // $mail->Password   = 'bddz qwmb App Pass Word'; // REMOVE trailing space
        // $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        // $mail->Port       = 587;

        // -----------------LIVE------------------
        $mail->setFrom('info@growingleaf.in', 'Growing Leaf Website');
        $mail->addReplyTo($email, $name);
        $mail->addAddress('growingleaf.ekm@gmail.com');
        $mail->addBCC('sinovthomas@gmail.com');
        $mail->addBCC('mrsunish@gmail.com');
        // -----------------LIVE END------------------

        $mail->isHTML(true);
        $mail->Subject = 'Growing Leaf Website-New Contact Form Submission from ' . $name;
        $mail->Body =
            "<h2>New Contact Form Submission</h2>
             <p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>
             <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
             <p><strong>Phone:</strong> " . htmlspecialchars($phone) . "</p>
             <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

        $mail->AltBody = "Name: $name\nEmail: $email\nMessage:\n$message";

        $mail->send();

        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Email sent successfully!']);
        exit;

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Mailer Error']);
        exit;
    }

} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}
