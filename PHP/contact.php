<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

$verificationHtml = ''; // Variable to hold the single entry HTML
$pageTitle = 'Contact Me';
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = htmlspecialchars(trim($_POST['name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $subject = htmlspecialchars(trim($_POST['subject']));
    $message = htmlspecialchars(trim($_POST['message']));
    
    $errorMsg = '';
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $errorMsg .= '<p>Please fill out all fields.</p>';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg .= '<p>Please enter a valid email address.</p>';
    }
    
    if (empty($errorMsg)) {
        // --- DATA FOLDER PATH ---
        $csvFile = '../data/contacts.csv';
        $fileHandle = fopen($csvFile, 'a');
        
        if (!file_exists($csvFile) || filesize($csvFile) == 0) {
            fputcsv($fileHandle, array('Timestamp', 'Name', 'Email', 'Subject', 'Message'));
        }
        
        date_default_timezone_set('America/Vancouver');
        $timestamp = date('Y-m-d H:i:s');
        $formData = array($timestamp, $name, $email, $subject, $message);
        fputcsv($fileHandle, $formData);
        fclose($fileHandle);
        
        // --- EMAIL NOTIFICATION (Optional) ---
        $to = 'ekarim4@my.bcit.ca';
        $email_subject = 'New Contact Form Submission: ' . $subject;
        $email_body = "You have received a new message.\n\nName: $name\nEmail: $email\n\nMessage:\n$message";
        $headers = "From: noreply@yourdomain.com\r\n" . "Reply-To: $email\r\n";
        // mail($to, $email_subject, $email_body, $headers); // Uncomment on live server
        
        $pageTitle = 'Message Sent!';
        
        // --- SUCCESS MESSAGE ---
        $successMessage = '<div class="alert alert-success shadow-lg" role="alert"><i class="fa-solid fa-check-circle"></i> <strong>Thank you!</strong> Your message has been sent successfully.</div>';

        // --- GENERATE LATEST ENTRY VERIFICATION ONLY ---
        // Instead of reading the whole CSV, we simply display the variables we just processed.
        $verificationHtml = '
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-dark text-white">
                <i class="fa-solid fa-file-invoice"></i> Submission Verification
            </div>
            <div class="card-body">
                <h5 class="card-title text-primary mb-3">Details Captured:</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><strong>Timestamp:</strong> ' . $timestamp . '</li>
                    <li class="list-group-item"><strong>Name:</strong> ' . $name . '</li>
                    <li class="list-group-item"><strong>Email:</strong> ' . $email . '</li>
                    <li class="list-group-item"><strong>Subject:</strong> ' . $subject . '</li>
                    <li class="list-group-item"><strong>Message:</strong><br><span class="text-muted">' . nl2br($message) . '</span></li>
                </ul>
            </div>
        </div>';
        
    } else {
        // Redirect back to HTML if errors exist
        header('Location: ../HTML/contact.html?status=error');
        exit();
    }
} else {
    // Redirect if someone tries to access php file directly without POST
    header('Location: ../HTML/contact.html');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Esha Karim - <?php echo htmlspecialchars($pageTitle); ?></title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="../css/styles.css" />
    </head>
    <body>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="#"><i class="fa-solid fa-earth-americas"></i> BCIT GIS</a>
                <div class="collapse navbar-collapse" id="navbarsExample04">
                    <ul class="navbar-nav me-auto mb-2 mb-md-0">
                        <li class="nav-item"><a class="nav-link" href="../index.html"><i class="fa-solid fa-house"></i> Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="../HTML/education.html"><i class="fa-solid fa-graduation-cap"></i> Education</a></li>
                        <li class="nav-item"><a class="nav-link" href="../HTML/courses.html"><i class="fa-solid fa-book-open"></i> Courses</a></li>
                        <li class="nav-item"><a class="nav-link" href="../HTML/software.html"><i class="fa-solid fa-laptop-code"></i> Software</a></li>
                        <li class="nav-item"><a class="nav-link" href="../HTML/mapgallery.html"><i class="fa-solid fa-map-location-dot"></i> Gallery</a></li>
                        <li class="nav-item"><a class="nav-link" href="../HTML/learning.html"><i class="fa-solid fa-lightbulb"></i> Learning</a></li>
                        <li class="nav-item"><a class="nav-link active" href="../HTML/contact.html"><i class="fa-solid fa-envelope"></i> Contact</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <header class="header" style="min-height: 40vh; padding-top: 80px; background: #001f3f;">
            <div class="text-center py-5">
                <h1 class="display-4 fw-bold text-white mt-4">Status Report</h1>
            </div>
        </header>

        <div class="container col-md-8 my-5">
             <?php echo $successMessage; ?>
             
             <?php echo $verificationHtml; ?>
             
             <div class="text-center mt-5">
                 <a href="../HTML/contact.html" class="btn btn-primary btn-lg"><i class="fa-solid fa-arrow-left"></i> Send Another Message</a>
             </div>
        </div>
        
        <footer class="footer mt-auto py-4 text-center">
            <div class="container"><span class="text-muted">&copy; 2025 Esha Karim.</span></div>
        </footer>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>