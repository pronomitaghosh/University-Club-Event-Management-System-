<?php
require_once 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name    = trim($_POST['full_name'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $department   = trim($_POST['department'] ?? '');
    $session_year = trim($_POST['session_year'] ?? '');
    $reason       = trim($_POST['reason'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $email === '' || $password === '') {
        $errors[] = "Name, email address, এবং password অবশ্যই দিতে হবে।";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "সঠিক একটি valid email address দিন। Email ঠিকানা student ID নয়।";
    }
    if ($password !== $confirm_pass) {
        $errors[] = "Password আর Confirm Password মিলছে না।";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password কমপক্ষে ৬ অক্ষরের হতে হবে।";
    }

    // Check email is not already a member or an existing pending request
    if (empty($errors)) {
        $stmt = $conn->prepare(
            "SELECT user_id FROM users WHERE email = ?
             UNION
             SELECT id FROM join_requests WHERE email = ?"
        );
        if ($stmt === false) {
            $errors[] = "ডাটাবেস ত্রুটি: " . $conn->error;
        } else {
            $stmt->bind_param("ss", $email, $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "এই email দিয়ে আগেই একটি request বা member account আছে।";
            }
            $stmt->close();
        }
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $status = 'pending';

        $stmt = $conn->prepare(
            "INSERT INTO join_requests
                (full_name, email, phone, department, session_year, reason, password, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        if ($stmt === false) {
            $errors[] = "ডাটাবেস প্রস্তুতি ত্রুটি: " . $conn->error;
        } else {
            $stmt->bind_param(
                "ssssssss",
                $full_name,
                $email,
                $phone,
                $department,
                $session_year,
                $reason,
                $hashed_password,
                $status
            );

            if ($stmt->execute()) {
                $success = true;
            } else {
                $errors[] = "কিছু একটা সমস্যা হয়েছে: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Us | CPC Kishoreganj University</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --teal: #1a7a6e;
            --teal-dark: #125a51;
            --deep: #0e3d38;
            --gold: #c89b3c;
            --white: #ffffff;
            --bg: #f4f9f8;
            --border: #cde5e2;
            --error: #c0392b;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 50px 20px;
        }

        .form-wrapper {
            width: 100%;
            max-width: 560px;
        }

        .form-header {
            background: linear-gradient(135deg, var(--deep) 0%, var(--teal) 100%);
            color: var(--white);
            padding: 28px 30px;
            border-radius: 16px 16px 0 0;
            text-align: center;
        }

        .form-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            margin-bottom: 6px;
        }

        .form-header p {
            opacity: 0.85;
            font-size: 0.9rem;
        }

        .form-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-top: none;
            border-radius: 0 0 16px 16px;
            padding: 30px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--deep);
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            color: var(--deep);
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--teal);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-submit {
            width: 100%;
            padding: 13px;
            background: var(--teal);
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
        }

        .btn-submit:hover {
            background: var(--teal-dark);
        }

        .alert {
            padding: 14px 16px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 0.9rem;
        }

        .alert-error {
            background: #fdecea;
            color: var(--error);
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #e9f7f0;
            color: var(--teal-dark);
            border: 1px solid var(--border);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--teal-dark);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        @media (max-width: 480px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <div class="form-header">
            <h1>Join CPC, Kishoreganj University</h1>
            <p>নিচের ফর্মটি পূরণ করে club membership এর জন্য request পাঠান। President approve করার পরই আপনি login করতে পারবেন।</p>
        </div>

        <div class="form-box">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ আপনার join request সফলভাবে জমা হয়েছে। President review করার পর আপনাকে জানানো হবে।
                </div>
                <a href="index.html" class="back-link">← Home এ ফিরে যান</a>
            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $err): ?>
                            <div><?= htmlspecialchars($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="join_request.php" autocomplete="off" novalidate>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" id="email" name="email" autocomplete="email" inputmode="email" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="<?= htmlspecialchars($_POST['department'] ?? '') ?>" placeholder="e.g. CSE">
                        </div>
                        <div class="form-group">
                            <label>Session / Year</label>
                            <input type="text" name="session_year" value="<?= htmlspecialchars($_POST['session_year'] ?? '') ?>" placeholder="e.g. 2022-23">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>কেন club এ join করতে চান?</label>
                        <textarea name="reason"><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" autocomplete="new-password" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Confirm Password *</label>
                            <input type="password" name="confirm_password" autocomplete="new-password" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Submit Join Request</button>
                </form>

                <a href="index.html" class="back-link">← Home এ ফিরে যান</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
