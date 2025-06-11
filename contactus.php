<?php
//

// Validate and sanitize input
$name = trim(htmlspecialchars($_POST['name'] ?? ''));
$email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
$phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
$student_grade = trim(htmlspecialchars($_POST['student_grade'] ?? ''));
$subject = trim(htmlspecialchars($_POST['subject'] ?? ''));
$message = trim(htmlspecialchars($_POST['message'] ?? ''));
$form_type = trim($_POST['form_type'] ?? '');

// Validate form type
if (!in_array($form_type, ['admission', 'contact'])) {
    $response['message'] = 'Invalid form submission.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Field validation
if (empty($name)) {
    $response['errors']['name'] = 'Full name is required.';
}

if (empty($email)) {
    $response['errors']['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['errors']['email'] = 'Please provide a valid email address.';
}

if ($form_type === 'admission') {
    if (empty($phone)) {
        $response['errors']['phone'] = 'Phone number is required for admissions.';
    }
    
    if (empty($student_grade)) {
        $response['errors']['student_grade'] = 'Please specify the student\'s grade level.';
    }
}

if (empty($subject)) {
    $response['errors']['subject'] = 'Subject is required.';
}

if (empty($message)) {
    $response['errors']['message'] = 'Message is required.';
} elseif (strlen($message) < 20) {
    $response['errors']['message'] = 'Please provide a more detailed message (at least 20 characters).';
}

// Check if there are any errors
if (!empty($response['errors'])) {
    $response['message'] = 'Please correct the highlighted errors.';
    http_response_code(422);
    echo json_encode($response);
    exit;
}

// Email configuration
$to = $form_type === 'admission' ? 'admissions@hekimaschool.edu' : 'info@hekimaschool.edu';
$email_subject = $form_type === 'admission' 
    ? "New Admission Inquiry: $subject" 
    : "New Contact Form Submission: $subject";

// Email headers
$headers = "From: $name <$email>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Cc: webmaster@hekimaschool.edu\r\n"; // Add CC for tracking
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

// Email body content
$email_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Playfair Display, serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #f8f9fa; padding: 10px; text-align: center; }
            .footer { margin-top: 20px; font-size: 0.9em; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>" . ($form_type === 'admission' ? 'New Admission Inquiry' : 'New Contact Form Submission') . "</h2>
            </div>
            
            <p><strong>From:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            " . ($phone ? "<p><strong>Phone:</strong> $phone</p>" : "") . "
            " . ($student_grade ? "<p><strong>Student Grade:</strong> $student_grade</p>" : "") . "
            <p><strong>Subject:</strong> $subject</p>
            <p><strong>Message:</strong></p>
            <div>" . nl2br($message) . "</div>
            
            <div class='footer'>
                <p><em>This message was sent from the " . ($form_type === 'admission' ? 'admissions' : 'contact') . " form on Hekima International School website.</em></p>
                <p>Received: " . date('Y-m-d H:i:s') . "</p>
            </div>
        </div>
    </body>
    </html>
";

// Additional headers for email tracking
$headers .= "X-Priority: 1 (Highest)\r\n";
$headers .= "X-MSMail-Priority: High\r\n";
$headers .= "Importance: High\r\n";

// Send email with error handling
try {
    $mailSent = mail($to, $email_subject, $email_body, $headers);
    
    if ($mailSent) {
        $response['success'] = true;
        http_response_code(200);
        
        if ($form_type === 'admission') {
            $response['message'] = 'Thank you for your application to Hekima International School!';
            $response['message'] .= "\n\nOur Admissions Office will review your information and contact you within 3-5 business days.";
            $response['message'] .= "\n\nFor urgent inquiries, please call our Admissions Office at +254705902590 or email admissions@hekimaschool.edu";
        } else {
            $response['message'] = 'Thank you for contacting Hekima International School!';
            $response['message'] .= "\n\nWe have received your message and our team will respond within 24-48 hours during school days (Monday-Friday).";
            $response['message'] .= "\n\nFor immediate assistance, please call our main office at +254705902590 (8:00 AM - 4:00 PM EAT).";
        }
        
        $response['message'] .= "\n\nPlease check your email (including spam folder) for our confirmation message.";
    } else {
        throw new Exception('Mail function returned false');
    }
} catch (Exception $e) {
    error_log('Email sending failed: ' . $e->getMessage());
    $response['message'] = 'We sincerely apologize for the inconvenience.';
    $response['message'] .= "\n\nOur system encountered difficulty processing your submission. Please try again in 15 minutes.";
    $response['message'] .= "\n\nIf the problem persists, please contact us directly:";
    $response['message'] .= "\n- Admissions: admissions@hekimaschool.edu / +254714405081";
    $response['message'] .= "\n- General Inquiries: info@hekimaschool.edu / +25422604638";
    $response['message'] .= "\n\nOffice Hours: Monday-Friday, 8:00 AM - 4:00 PM East Africa Time";
    http_response_code(500);
}



header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => []
];

// Return JSON response
echo json_encode($response);
?>