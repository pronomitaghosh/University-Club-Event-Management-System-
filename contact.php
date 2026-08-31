<?php
session_start();
require_once 'db.php';
ensure_contact_message_schema($conn);

$success_msg = "";
$error_msg = "";

// Form submit handle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $message === '') {
        $error_msg = "সব ঘর পূরণ করুন (ফোন optional)।";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "সঠিক Email address দিন।";
    } else {
        $columns = get_contact_message_columns($conn);
        $set = [];
        $values = [];
        $types = "";

        $set[] = "name";
        $values[] = $name;
        $types .= "s";

        $set[] = "email";
        $values[] = $email;
        $types .= "s";

        if (in_array('phone', $columns, true)) {
            $set[] = "phone";
            $values[] = $phone;
            $types .= "s";
        }

        $set[] = "message";
        $values[] = $message;
        $types .= "s";

        if (in_array('is_read', $columns, true)) {
            $set[] = "is_read";
            $values[] = 0;
            $types .= "i";
        }

        if (in_array('status', $columns, true) && !in_array('is_read', $columns, true)) {
            $set[] = "status";
            $values[] = "pending";
            $types .= "s";
        }

        $sql = "INSERT INTO contact_messages (" . implode(", ", $set) . ") VALUES (" . implode(", ", array_fill(0, count($set), "?")) . ")";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param($types, ...$values);
            if ($stmt->execute()) {
                $success_msg = "Your message has been sent successfully. The president will review it soon.";
            } else {
                $error_msg = "Something went wrong while sending your message. Please try again.";
            }
            $stmt->close();
        } else {
            $error_msg = "Database insert failed. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | CPC</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght=700;900&family=DM+Sans:wght=400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }
        .info-link {
            color: var(--teal);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }
        .info-link:hover {
            color: var(--gold);
        }
        .map-container {
            margin-top: 20px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #cde5e2;
            height: 250px;
        }
        .alert-box {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.html" class="nav-logo">
            <img src="ClubLogo.jpg" alt="Logo" style="width: 35px; height: 35px; object-fit: contain;">
            <span>Computer & Programming Club<br>Kishoreganj University</span>
        </a>
        <div class="hamburger"><span></span><span></span><span></span></div>
        <div class="nav-container">
            <div class="nav-links">
                <a href="index.html">Home</a>
                <a href="about.html">About</a>
                <a href="committee.html">Committee</a>
                <a href="events.html">Events</a>
                <a href="achievements.html">Achievements</a>
                <a href="resources.html">Resources</a>
                <a href="contact.php" class="active">Contact</a>
            </div>
        </div>
    </nav>

    <header class="page-header">
        <h1>Contact Us</h1>
        <p>Have questions? Reach out to us anytime.</p>
    </header>

    <main class="main-content">
        <div class="contact-container">
            <div class="card">
                <h3 style="color: var(--deep); margin-bottom: 20px; font-family: 'Playfair Display', serif; font-size: 1.6rem;">Get In Touch</h3>
                <p style="margin-bottom: 12px;"><strong>📍 Location:</strong> Kishoreganj University Campus, Kishoreganj.</p>
                <p style="margin-bottom: 12px;"><strong>✉️ Email:</strong> <a href="mailto:bcpc.bsmru.ac.bd" class="info-link">bcpc.bsmru.ac.bd</a></p>
                <p style="margin-bottom: 12px;"><strong>🌐 Facebook:</strong> <a href="https://www.facebook.com/share/1HBRPMF6AT/" target="_blank" class="info-link">facebook.com/cpckiu</a></p>
                <p style="margin-bottom: 20px;"><strong>💼 LinkedIn:</strong> <a href="https://linkedin.com" target="_blank" class="info-link">linkedin.com/company/cpckuni</a></p>

                <div class="map-container">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3630.9348981442125!2d90.7937403!3d24.4877395!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x375693ff79da5a8b%3A0xc3b8a1c9703c14d9!2sBangabandhu%20Sheikh%20Mujibur%20Rahman%20University%2C%20Kishoreganj!5e0!3m2!1sen!2sbd!4v1710000000000!5m2!1sen!2sbd"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div>

            <div class="card">
                <h3 style="color: var(--deep); margin-bottom: 20px; font-family: 'Playfair Display', serif; font-size: 1.6rem;">Send a Message</h3>

                <?php if ($success_msg): ?>
                    <div class="alert-box alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php endif; ?>

                <?php if ($error_msg): ?>
                    <div class="alert-box alert-error"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>

                <form method="POST" action="contact.php" style="display: flex; flex-direction: column; gap: 15px;">
                    <input type="text" name="name" placeholder="Your Name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" style="padding: 12px; border: 1px solid #cde5e2; border-radius: 8px; width: 100%; font-family: inherit;" required>
                    <input type="email" name="email" placeholder="Your Email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" style="padding: 12px; border: 1px solid #cde5e2; border-radius: 8px; width: 100%; font-family: inherit;" required>
                    <input type="text" name="phone" placeholder="Your Phone (Optional)" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" style="padding: 12px; border: 1px solid #cde5e2; border-radius: 8px; width: 100%; font-family: inherit;">
                    <textarea name="message" placeholder="Your Message" rows="5" style="padding: 12px; border: 1px solid #cde5e2; border-radius: 8px; width: 100%; font-family: inherit;" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    <button type="submit" class="btn-nav btn-reg-nav" style="border: none; cursor: pointer; padding: 14px; font-size: 1rem;">Send Message</button>
                </form>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>