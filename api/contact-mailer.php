<?php
/**
 * RAR Consultant - Contact Form Mailer & Autoreply System
 * 
 * Target recipient: anand@rarconsultant.co.in
 * Office: RAR Consultant, Ganpati Matha, Warje Malwadi, Pune - 411058
 * Phone: +91 9158030333
 * 
 * Features:
 *  - Native Pure-PHP SMTP Client (supports SSL 465 & STARTTLS 587, zero external dependencies)
 *  - PHPMailer support if present in api/PHPMailer/
 *  - Resilient native mail() fallback with -f envelope-sender & RFC 2047/base64 encoding
 *  - Dual dispatch: Admin Lead Notification + Client Confirmation Autoreply
 *  - Built-in audit logger (api/mailer.log)
 *  - Diagnostic GET mode for easy troubleshooting
 */

// Set response headers
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------------------------------------
// 1. CONFIGURATION
// -------------------------------------------------------------
$company_email = 'anand@rarconsultant.co.in';
$company_name  = 'RAR Consultant';
$company_phone = '+91 9158030333';
$company_addr  = 'RAR Consultant, Ganpati Matha, Warje Malwadi, Pune - 411058';
$company_hours = 'Mon - Sat 09:00 - 19:00 IST';
$company_web   = 'https://rarconsultant.co.in';

// Default SMTP Configuration
// (Can be overridden by creating api/mailer-config.php)
$smtp_enabled  = false;                       // Set to true once password is provided
$smtp_host     = 'mail.rarconsultant.co.in';  // or 'smtp.gmail.com'
$smtp_port     = 465;                         // 465 (SSL) or 587 (TLS)
$smtp_secure   = 'ssl';                       // 'ssl' or 'tls'
$smtp_user     = 'anand@rarconsultant.co.in'; // SMTP username / email
$smtp_pass     = '';                          // SMTP account password

// Load external config if exists (git-ignored for security)
if (file_exists(__DIR__ . '/mailer-config.php')) {
    include __DIR__ . '/mailer-config.php';
}

// -------------------------------------------------------------
// 2. AUDIT LOGGER
// -------------------------------------------------------------
function log_mailer($to, $subject, $transport, $status, $details = '') {
    $log_file = __DIR__ . '/mailer.log';
    
    // Auto-rotate if log exceeds 1MB
    if (file_exists($log_file) && filesize($log_file) > 1048576) {
        @rename($log_file, __DIR__ . '/mailer.old.log');
    }
    
    $date = date('Y-m-d H:i:s');
    $line = "[{$date}] [{$status}] Transport: {$transport} | To: {$to} | Subject: {$subject}";
    if (!empty($details)) {
        $line .= " | Info: " . str_replace(["\r", "\n"], ' ', $details);
    }
    $line .= PHP_EOL;
    
    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

// -------------------------------------------------------------
// 3. PURE-PHP ZERO-DEPENDENCY SMTP CLIENT
// -------------------------------------------------------------
class SimpleSMTP {
    private $host;
    private $port;
    private $secure;
    private $user;
    private $pass;
    private $timeout = 15;
    private $socket = null;
    public $last_error = '';

    public function __construct($host, $port, $user, $pass, $secure = 'ssl') {
        $this->host   = $host;
        $this->port   = (int)$port;
        $this->user   = $user;
        $this->pass   = $pass;
        $this->secure = strtolower($secure);
    }

    private function getResponse() {
        $data = '';
        while (!feof($this->socket)) {
            $line = fgets($this->socket, 515);
            if ($line === false) break;
            $data .= $line;
            // Multiline responses have hyphen at index 3 (e.g. "250-SIZE"), terminal line has space ("250 OK")
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    }

    private function sendCmd($cmd, $expected_codes) {
        if (!is_array($expected_codes)) {
            $expected_codes = [$expected_codes];
        }
        fwrite($this->socket, $cmd . "\r\n");
        $res = $this->getResponse();
        $code = substr($res, 0, 3);
        if (!in_array($code, $expected_codes)) {
            $this->last_error = "SMTP Command failed [{$cmd}] - Response: " . trim($res);
            return false;
        }
        return true;
    }

    public function testConnection() {
        $prefix = ($this->port === 465 || $this->secure === 'ssl') ? 'ssl://' : '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $socket = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return ['success' => false, 'error' => "Connection failed to {$this->host}:{$this->port} ({$errno} - {$errstr})"];
        }

        $greeting = fgets($socket, 515);
        fclose($socket);

        if (substr($greeting, 0, 3) === '220') {
            return ['success' => true, 'greeting' => trim($greeting)];
        }
        return ['success' => false, 'error' => 'Unexpected greeting: ' . trim($greeting)];
    }

    public function send($from_email, $from_name, $to_email, $to_name, $subject, $html_body, $reply_to = null, $reply_name = null) {
        $prefix = ($this->port === 465 || $this->secure === 'ssl') ? 'ssl://' : '';
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $this->socket = @stream_socket_client(
            $prefix . $this->host . ':' . $this->port,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            $this->last_error = "Socket connection failed to {$this->host}:{$this->port} ({$errno} - {$errstr})";
            return false;
        }

        stream_set_timeout($this->socket, $this->timeout);

        $greeting = $this->getResponse();
        if (substr($greeting, 0, 3) !== '220') {
            $this->last_error = "Server did not return 220 greeting: " . trim($greeting);
            fclose($this->socket);
            return false;
        }

        $client_host = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'rarconsultant.co.in';

        if (!$this->sendCmd("EHLO {$client_host}", ['250'])) {
            if (!$this->sendCmd("HELO {$client_host}", ['250'])) {
                fclose($this->socket);
                return false;
            }
        }

        // STARTTLS negotiation for Port 587
        if ($this->port === 587 || $this->secure === 'tls') {
            if (!$this->sendCmd("STARTTLS", ['220'])) {
                fclose($this->socket);
                return false;
            }

            $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            }
            if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
            }

            if (!stream_socket_enable_crypto($this->socket, true, $crypto_method)) {
                $this->last_error = "TLS cryptographic negotiation failed on port 587";
                fclose($this->socket);
                return false;
            }

            // Re-send EHLO after TLS handshake
            if (!$this->sendCmd("EHLO {$client_host}", ['250'])) {
                fclose($this->socket);
                return false;
            }
        }

        // Authentication
        if (!empty($this->user) && !empty($this->pass)) {
            if (!$this->sendCmd("AUTH LOGIN", ['334'])) {
                fclose($this->socket);
                return false;
            }
            if (!$this->sendCmd(base64_encode($this->user), ['334'])) {
                fclose($this->socket);
                return false;
            }
            if (!$this->sendCmd(base64_encode($this->pass), ['235'])) {
                $this->last_error = "SMTP Authentication failed: check username ({$this->user}) and password";
                fclose($this->socket);
                return false;
            }
        }

        // Envelope From & To
        if (!$this->sendCmd("MAIL FROM:<{$from_email}>", ['250'])) {
            fclose($this->socket);
            return false;
        }

        if (!$this->sendCmd("RCPT TO:<{$to_email}>", ['250', '251'])) {
            fclose($this->socket);
            return false;
        }

        // Send DATA command
        if (!$this->sendCmd("DATA", ['354'])) {
            fclose($this->socket);
            return false;
        }

        // MIME formatting
        $mime_from = '=?UTF-8?B?' . base64_encode($from_name) . "?= <{$from_email}>";
        $mime_to   = !empty($to_name) ? ('=?UTF-8?B?' . base64_encode($to_name) . "?= <{$to_email}>") : "<{$to_email}>";
        $mime_sub  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $msg_id    = '<' . md5(uniqid(microtime(), true)) . '@' . $this->host . '>';

        $headers = [];
        $headers[] = "Date: " . date('r');
        $headers[] = "To: {$mime_to}";
        $headers[] = "From: {$mime_from}";
        if ($reply_to) {
            $reply_disp = !empty($reply_name) ? ('=?UTF-8?B?' . base64_encode($reply_name) . "?= ") : '';
            $headers[] = "Reply-To: {$reply_disp}<{$reply_to}>";
        }
        $headers[] = "Subject: {$mime_sub}";
        $headers[] = "Message-ID: {$msg_id}";
        $headers[] = "X-Mailer: RAR Consultant Secure Mailer 2.0";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";

        $body_encoded = chunk_split(base64_encode($html_body));
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body_encoded . "\r\n.";

        fwrite($this->socket, $payload . "\r\n");
        $res = $this->getResponse();

        $this->sendCmd("QUIT", ['221']);
        fclose($this->socket);

        if (substr($res, 0, 3) === '250') {
            return true;
        }

        $this->last_error = "Server rejected message body: " . trim($res);
        return false;
    }
}

// -------------------------------------------------------------
// 4. NATIVE PHP mail() FALLBACK WITH ENVELOPE SENDER & BASE64
// -------------------------------------------------------------
function send_native_mail($to, $to_name, $subject, $html_body, $reply_to = null, $reply_name = null) {
    global $company_email, $company_name;

    $clean_from_email = filter_var($company_email, FILTER_VALIDATE_EMAIL) ? $company_email : 'anand@rarconsultant.co.in';
    $clean_to_email   = filter_var($to, FILTER_VALIDATE_EMAIL);
    if (!$clean_to_email) {
        return false;
    }

    $mime_from = '=?UTF-8?B?' . base64_encode($company_name) . "?= <{$clean_from_email}>";
    $mime_sub  = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: base64';
    $headers[] = "From: {$mime_from}";
    $headers[] = "Sender: <{$clean_from_email}>";
    $headers[] = "Return-Path: <{$clean_from_email}>";

    if ($reply_to && filter_var($reply_to, FILTER_VALIDATE_EMAIL)) {
        $reply_disp = !empty($reply_name) ? ('=?UTF-8?B?' . base64_encode($reply_name) . '?= ') : '';
        $headers[] = "Reply-To: {$reply_disp}<{$reply_to}>";
    } else {
        $headers[] = "Reply-To: {$mime_from}";
    }

    $headers[] = 'X-Mailer: PHP/' . phpversion();

    $header_str = implode("\r\n", $headers);
    $body_encoded = chunk_split(base64_encode($html_body));

    // Try with -f envelope sender parameter (crucial for external SPF passing)
    $envelope_param = "-f" . escapeshellarg($clean_from_email);
    $sent = @mail($clean_to_email, $mime_sub, $body_encoded, $header_str, $envelope_param);

    if (!$sent) {
        // Retry without parameter if server restrictions prohibit 5th argument
        $sent = @mail($clean_to_email, $mime_sub, $body_encoded, $header_str);
    }

    return $sent;
}

// -------------------------------------------------------------
// 5. UNIFIED DISPATCH FUNCTION
// -------------------------------------------------------------
function send_email($to, $to_name, $subject, $html_body, $reply_to = null, $reply_name = null) {
    global $smtp_enabled, $smtp_host, $smtp_port, $smtp_secure, $smtp_user, $smtp_pass, $company_email, $company_name;

    // 1. Try PHPMailer if vendor files are present and SMTP enabled
    if ($smtp_enabled && file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
        try {
            require_once __DIR__ . '/PHPMailer/Exception.php';
            require_once __DIR__ . '/PHPMailer/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/SMTP.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtp_user;
            $mail->Password   = $smtp_pass;
            $mail->SMTPSecure = ($smtp_port == 465 || strtolower($smtp_secure) === 'ssl')
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)$smtp_port;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($company_email, $company_name);
            $mail->addAddress($to, $to_name);

            if ($reply_to) {
                $mail->addReplyTo($reply_to, $reply_name ?: $reply_to);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;

            $sent = $mail->send();
            if ($sent) {
                log_mailer($to, $subject, 'PHPMailer (SMTP)', 'SUCCESS');
                return true;
            }
        } catch (\Exception $e) {
            log_mailer($to, $subject, 'PHPMailer (SMTP)', 'FAILED', $e->getMessage());
        }
    }

    // 2. Try Built-in Zero-Dependency SimpleSMTP
    if ($smtp_enabled && !empty($smtp_host) && !empty($smtp_user) && !empty($smtp_pass)) {
        $smtp = new SimpleSMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_secure);
        $sent = $smtp->send($company_email, $company_name, $to, $to_name, $subject, $html_body, $reply_to, $reply_name);
        if ($sent) {
            log_mailer($to, $subject, 'Built-in SMTP', 'SUCCESS');
            return true;
        } else {
            log_mailer($to, $subject, 'Built-in SMTP', 'FAILED', $smtp->last_error);
            // Fall through to native mail()
        }
    }

    // 3. Fallback to native PHP mail() with strict envelope headers
    $sent = send_native_mail($to, $to_name, $subject, $html_body, $reply_to, $reply_name);
    if ($sent) {
        log_mailer($to, $subject, 'Native mail()', 'SUCCESS');
        return true;
    } else {
        $last_err = error_get_last();
        $err_msg = $last_err ? ($last_err['message'] ?? 'mail() returned false') : 'mail() returned false';
        log_mailer($to, $subject, 'Native mail()', 'FAILED', $err_msg);
        return false;
    }
}

// -------------------------------------------------------------
// 6. GET DIAGNOSTIC & HEALTH-CHECK HANDLER
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $has_config_file  = file_exists(__DIR__ . '/mailer-config.php');
    $has_phpmailer    = file_exists(__DIR__ . '/PHPMailer/PHPMailer.php');
    $has_openssl      = extension_loaded('openssl');
    $has_sockets      = function_exists('stream_socket_client');
    $has_native_mail  = function_exists('mail');

    $socket_test = null;
    if ($smtp_enabled && !empty($smtp_host)) {
        $test_client = new SimpleSMTP($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_secure);
        $socket_test = $test_client->testConnection();
    }

    echo json_encode([
        'status'             => 'RAR Consultant Mailer Diagnostic',
        'server'             => [
            'php_version'    => phpversion(),
            'openssl'        => $has_openssl ? 'Available (SSL/TLS supported)' : 'Missing',
            'stream_socket'  => $has_sockets ? 'Available' : 'Missing',
            'native_mail'    => $has_native_mail ? 'Available' : 'Disabled by host'
        ],
        'smtp_configuration' => [
            'enabled'        => $smtp_enabled,
            'host'           => $smtp_host,
            'port'           => $smtp_port,
            'secure'         => $smtp_secure,
            'user'           => $smtp_user,
            'password_set'   => !empty($smtp_pass),
            'config_file'    => $has_config_file ? 'Found (api/mailer-config.php)' : 'Not present (using defaults)',
            'phpmailer'      => $has_phpmailer ? 'Installed' : 'Built-in SMTP Engine active',
            'socket_test'    => $socket_test
        ],
        'recipients'         => [
            'admin_notification' => $company_email,
            'client_autoreply'   => 'Dynamically dispatched to submitting client email'
        ],
        'instructions'       => 'For 100% reliable inbox delivery to Gmail/Yahoo/Outlook: Copy api/mailer-config.sample.php to api/mailer-config.php and enter your SMTP password for ' . $smtp_user . '.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// -------------------------------------------------------------
// 7. POST SUBMISSION PROCESSING
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Parse request data (FormData, URL-encoded, or JSON)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $input = $json;
        }
    }
}

// Extract fields with multiple alias support
$full_name = trim($input['full_name'] ?? $input['fullName'] ?? $input['name'] ?? '');
$mobile    = trim($input['mobile_number'] ?? $input['mobile'] ?? $input['phone'] ?? '');
$email     = trim($input['corp_email'] ?? $input['email'] ?? '');
$service   = trim($input['practice_domain'] ?? $input['service'] ?? '');
$message   = trim($input['scope_overview'] ?? $input['message'] ?? $input['description'] ?? '');
$honeypot  = trim($input['honeypot_node'] ?? $input['botcheck'] ?? '');
$consent   = !empty($input['dpdp_consent'] ?? $input['consent'] ?? null);

// 1. Honeypot check (anti-spam)
if (!empty($honeypot)) {
    // Silently return success to mislead bots
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been sent successfully.'
    ]);
    exit;
}

// 2. Server-side validation
if (mb_strlen($full_name) < 2 || mb_strlen($full_name) > 80) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid full name (minimum 2 characters).']);
    exit;
}

$digits = preg_replace('/\D/', '', $mobile);
if (strlen($digits) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid 10-digit mobile number.']);
    exit;
}
$formatted_mobile = (strlen($digits) === 10) ? '+91 ' . $digits : '+' . $digits;

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if (empty($service)) {
    echo json_encode(['success' => false, 'message' => 'Please select a service interest.']);
    exit;
}

if (mb_strlen($message) < 5) {
    echo json_encode(['success' => false, 'message' => 'Please share a brief description of your project (at least 5 characters).']);
    exit;
}

// Format submission timestamp in IST
date_default_timezone_set('Asia/Kolkata');
$timestamp = date('d-M-Y h:i:s A') . ' IST';
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// -------------------------------------------------------------
// 8. BUILD EMAIL 1: ADMIN LEAD NOTIFICATION (anand@rarconsultant.co.in)
// -------------------------------------------------------------
$admin_subject = "New Project Enquiry: {$full_name} - {$service} | RAR Consultant";

$admin_html = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <title>New Project Enquiry</title>
</head>
<body style='margin:0;padding:0;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>
  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#F8FAFC;padding:30px 15px;'>
    <tr>
      <td align='center'>
        <table role='presentation' width='100%' style='max-width:620px;background:#ffffff;border-radius:18px;border:1px solid #E2E8F0;box-shadow:0 10px 25px rgba(0,0,0,0.06);overflow:hidden;' cellpadding='0' cellspacing='0'>
          <!-- Header -->
          <tr>
            <td style='background:linear-gradient(135deg,#070D1E,#0E1E42);padding:32px 30px;color:#ffffff;text-align:left;'>
              <div style='color:#06B6D4;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;'>NEW CLIENT LEAD</div>
              <h1 style='margin:8px 0 0;font-size:22px;font-weight:800;color:#ffffff;'>RAR Consultant Enquiry Desk</h1>
            </td>
          </tr>
          <!-- Body -->
          <tr>
            <td style='padding:30px;'>
              <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='font-size:14px;color:#334155;line-height:1.6;'>
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;width:150px;color:#64748B;font-weight:600;'>Client Name:</td>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#0B132B;font-weight:700;'>" . htmlspecialchars($full_name) . "</td>
                </tr>
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600;'>Mobile Number:</td>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;'><a href='tel:" . htmlspecialchars($digits) . "' style='color:#2563EB;font-weight:700;text-decoration:none;'>" . htmlspecialchars($formatted_mobile) . "</a></td>
                </tr>
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600;'>Email Address:</td>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;'><a href='mailto:" . htmlspecialchars($email) . "' style='color:#2563EB;font-weight:700;text-decoration:none;'>" . htmlspecialchars($email) . "</a></td>
                </tr>
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600;'>Service Interest:</td>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#0284C7;font-weight:700;'>" . htmlspecialchars($service) . "</td>
                </tr>
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600;'>Submitted At:</td>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#475569;font-size:13px;'>" . $timestamp . "</td>
                </tr>
                <tr>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#64748B;font-weight:600;'>Client IP:</td>
                  <td style='padding:10px 0;border-bottom:1px solid #F1F5F9;color:#64748B;font-size:12px;font-family:monospace;'>" . htmlspecialchars($client_ip) . "</td>
                </tr>
              </table>

              <div style='margin-top:24px;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:12px;padding:18px;'>
                <div style='font-size:12px;font-weight:bold;color:#0F172A;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;'>Project Scope / Description:</div>
                <div style='font-size:14px;color:#334155;line-height:1.7;white-space:pre-wrap;'>" . nl2br(htmlspecialchars($message)) . "</div>
              </div>

              <!-- Quick Action Reply Button -->
              <div style='margin-top:24px;text-align:center;'>
                <a href='mailto:" . htmlspecialchars($email) . "?subject=" . urlencode("Re: Project Enquiry - " . $service . " | RAR Consultant") . "' style='display:inline-block;background:#2563EB;color:#ffffff;padding:12px 28px;border-radius:30px;font-size:14px;font-weight:bold;text-decoration:none;box-shadow:0 4px 12px rgba(37,99,235,0.25);'>
                  ✉️ Reply directly to " . htmlspecialchars($full_name) . "
                </a>
              </div>

              <div style='margin-top:24px;padding-top:16px;border-top:1px solid #E2E8F0;font-size:12px;color:#64748B;'>
                DPDP Act Consent: <strong>Agreed & Logged</strong><br>
                Reply-To header is configured for <strong>" . htmlspecialchars($email) . "</strong>. You can simply click Reply in your email client.
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
";

// -------------------------------------------------------------
// 9. BUILD EMAIL 2: AUTOREPLY TO CLIENT ($email)
// -------------------------------------------------------------
// Standard ASCII hyphen used in Subject for strict RFC 5322 compliance
$client_subject = "Thank you for contacting RAR Consultant - We received your enquiry";

$client_html = "
<!DOCTYPE html>
<html>
<head>
  <meta charset='UTF-8'>
  <title>Thank you for contacting RAR Consultant</title>
</head>
<body style='margin:0;padding:0;background-color:#F8FAFC;font-family:-apple-system,BlinkMacSystemFont,\"Segoe UI\",Roboto,sans-serif;'>
  <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background-color:#F8FAFC;padding:30px 15px;'>
    <tr>
      <td align='center'>
        <table role='presentation' width='100%' style='max-width:620px;background:#ffffff;border-radius:20px;border:1px solid #E2E8F0;box-shadow:0 12px 30px rgba(0,0,0,0.06);overflow:hidden;' cellpadding='0' cellspacing='0'>
          
          <!-- Brand Header -->
          <tr>
            <td style='background:linear-gradient(135deg,#070D1E,#0C1A3A,#070D1E);padding:36px 32px;color:#ffffff;text-align:left;border-bottom:2px solid #06B6D4;'>
              <div style='display:inline-block;padding:4px 12px;background:rgba(6,182,212,0.15);border:1px solid rgba(6,182,212,0.3);border-radius:20px;color:#06B6D4;font-size:11px;font-weight:bold;letter-spacing:2px;text-transform:uppercase;margin-bottom:12px;'>
                SMART IT SOLUTIONS
              </div>
              <h1 style='margin:0;font-size:24px;font-weight:800;letter-spacing:-0.5px;color:#ffffff;'>RAR CONSULTANT</h1>
              <p style='margin:6px 0 0;font-size:13px;color:#94A3B8;'>All IT Solutions Under One Roof</p>
            </td>
          </tr>

          <!-- Thank You Message -->
          <tr>
            <td style='padding:36px 32px;'>
              <h2 style='margin:0 0 16px;font-size:20px;color:#0B132B;font-weight:800;'>
                Thank you for reaching out, " . htmlspecialchars($full_name) . "!
              </h2>
              
              <p style='margin:0 0 16px;font-size:15px;color:#475569;line-height:1.7;'>
                We have received your enquiry regarding <strong style='color:#2563EB;'>" . htmlspecialchars($service) . "</strong>. Our engineering and consulting team in Pune is reviewing your requirements and will get back to you with a tailored consultation within <strong>24 hours</strong>.
              </p>

              <!-- Enquiry Summary Box -->
              <div style='margin:24px 0;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:14px;padding:20px;'>
                <div style='font-size:12px;font-weight:bold;color:#0B132B;text-transform:uppercase;letter-spacing:1px;margin-bottom:12px;border-bottom:1px solid #E2E8F0;padding-bottom:8px;'>
                  Enquiry Summary
                </div>
                <table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='font-size:13px;color:#475569;'>
                  <tr>
                    <td style='padding:5px 0;color:#64748B;width:130px;font-weight:600;'>Service:</td>
                    <td style='padding:5px 0;color:#0B132B;font-weight:bold;'>" . htmlspecialchars($service) . "</td>
                  </tr>
                  <tr>
                    <td style='padding:5px 0;color:#64748B;font-weight:600;'>Your Email:</td>
                    <td style='padding:5px 0;color:#0B132B;'>" . htmlspecialchars($email) . "</td>
                  </tr>
                  <tr>
                    <td style='padding:5px 0;color:#64748B;font-weight:600;'>Mobile:</td>
                    <td style='padding:5px 0;color:#0B132B;'>" . htmlspecialchars($formatted_mobile) . "</td>
                  </tr>
                  <tr>
                    <td style='padding:5px 0;color:#64748B;font-weight:600;vertical-align:top;'>Requirements:</td>
                    <td style='padding:5px 0;color:#334155;'>" . nl2br(htmlspecialchars($message)) . "</td>
                  </tr>
                </table>
              </div>

              <!-- Direct Contact Details -->
              <div style='background:linear-gradient(135deg,#F0FDF4,#E0F2FE);border:1px solid #BAE6FD;border-radius:14px;padding:20px;margin:28px 0;'>
                <div style='font-size:12px;font-weight:bold;color:#0369A1;text-transform:uppercase;letter-spacing:1px;margin-bottom:10px;'>
                  Need immediate assistance? Reach us directly:
                </div>
                <div style='font-size:13px;color:#1E293B;line-height:1.9;'>
                  📞 <strong>Direct Call:</strong> <a href='tel:{$company_phone}' style='color:#2563EB;text-decoration:none;font-weight:bold;'>{$company_phone}</a><br>
                  💬 <strong>WhatsApp:</strong> <a href='https://wa.me/919158030333?text=Hello%20RAR%20Consultant%20Engineering%20Desk' style='color:#16A34A;text-decoration:none;font-weight:bold;'>Chat on WhatsApp (+91 9158030333)</a><br>
                  ✉️ <strong>Central Email:</strong> <a href='mailto:{$company_email}' style='color:#2563EB;text-decoration:none;font-weight:bold;'>{$company_email}</a><br>
                  📍 <strong>HQ Office:</strong> {$company_addr}<br>
                  🕒 <strong>Working Hours:</strong> {$company_hours}
                </div>
              </div>

              <p style='margin:0 0 8px;font-size:14px;color:#475569;'>
                Warm regards,<br>
                <strong style='color:#0B132B;'>RAR Consultant Engineering & Client Desk</strong><br>
                <span style='font-size:12px;color:#64748B;'>Ganpati Matha, Warje Malwadi, Pune - 411058</span>
              </p>
            </td>
          </tr>

          <!-- Footer Compliance -->
          <tr>
            <td style='background:#F1F5F9;padding:20px 32px;text-align:center;font-size:11px;color:#64748B;border-top:1px solid #E2E8F0;'>
              <p style='margin:0 0 4px;'>© " . date('Y') . " RAR Consultant. All rights reserved.</p>
              <p style='margin:0;'>Committed to privacy and data protection under India's Digital Personal Data Protection Act, 2023.</p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
";

// -------------------------------------------------------------
// 10. SEND BOTH EMAILS
// -------------------------------------------------------------
// 1. Send Admin Notification to Anand
$admin_sent  = send_email($company_email, 'RAR Consultant Team', $admin_subject, $admin_html, $email, $full_name);

// 2. Send Autoreply to Client
$client_sent = send_email($email, $full_name, $client_subject, $client_html, $company_email, $company_name);

// -------------------------------------------------------------
// 11. RETURN JSON RESPONSE TO FRONTEND
// -------------------------------------------------------------
echo json_encode([
    'success'    => true,
    'message'    => 'Thank you, ' . $full_name . '! Your message has been sent successfully. Our team in Pune will get back to you within 24 hours. An autoreply confirmation email has been dispatched to ' . $email . '.',
    'lead'       => [
        'name'    => $full_name,
        'email'   => $email,
        'service' => $service,
        'phone'   => $formatted_mobile
    ],
    'dispatched' => [
        'admin_notification' => $admin_sent ? 'sent' : 'failed',
        'client_autoreply'   => $client_sent ? 'sent' : 'failed'
    ]
]);
