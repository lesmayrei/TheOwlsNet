<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'DB.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = (int)$_SESSION['user_id'];

$currentPicture = '';
$currentBio = '';
$msg = '';

// load current profile info
if (isset($conn) && !$conn->connect_error) {
    $stmt = $conn->prepare('SELECT picture, profile_status FROM profiles WHERE user_id = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($pic, $status);
        if ($stmt->fetch()) {
            $currentPicture = $pic ?: '';
            $currentBio = $status ?: '';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $newPicture = $currentPicture;

    // handle file upload if a new file was selected
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profile_picture']['tmp_name'];
        $originalName = basename($_FILES['profile_picture']['name']);

        // simple target folder and filename
        $targetDir = 'uploads/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $targetPath = $targetDir . time() . '_' . preg_replace('/\s+/', '_', $originalName);

        if (move_uploaded_file($tmpName, $targetPath)) {
            $newPicture = $targetPath;
        } else {
            $msg = 'There was a problem uploading the file.';
        }
    }

    if (isset($conn) && !$conn->connect_error) {
        $stmt = $conn->prepare('UPDATE profiles SET picture = ?, profile_status = ? WHERE user_id = ?');
        if ($stmt) {
            $stmt->bind_param('ssi', $newPicture, $bio, $userId);
            if ($stmt->execute()) {
                $msg = 'Profile updated.';
                $currentPicture = $newPicture;
                $currentBio = $bio;
            } else {
                $msg = 'Could not save changes.';
            }
            $stmt->close();
        } else {
            $msg = 'Could not save changes.';
        }
    } else {
        $msg = 'Database connection error.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile – The Owls Net</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #0f172a;
            color: #f9fafb;
            font-family: system-ui, sans-serif;
            margin: 0;
        }

        header {
            background: #020617;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #1f2937;
        }

        .logo {
            font-weight: 700;
            letter-spacing: 0.05em;
        }

        nav a {
            color: #e5e7eb;
            margin-left: 1rem;
            text-decoration: none;
            font-size: 0.95rem;
        }

        nav a:hover {
            text-decoration: underline;
        }

        main {
            max-width: 640px;
            margin: 2.5rem auto 3rem auto;
            padding: 0 1.5rem;
        }

        h1 {
            font-size: 1.7rem;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            font-size: 0.95rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1.25rem 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
            color: #cbd5e1;
        }

        input[type="file"] {
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        textarea {
            width: 100%;
            min-height: 110px;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f9fafb;
            font-size: 0.95rem;
            padding: 0.6rem 0.7rem;
            font-family: inherit;
            resize: vertical;
        }

        textarea:focus {
            outline: none;
            border-color: #38bdf8;
        }

        .current-picture {
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #9ca3af;
        }

        .current-picture img {
            display: block;
            margin-top: 0.5rem;
            max-width: 120px;
            border-radius: 999px;
            border: 2px solid #38bdf8;
        }

        .btn-row {
            margin-top: 1rem;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.55rem 1.3rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #38bdf8;
            color: #020617;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #0ea5e9;
        }

        .btn-secondary-link {
            text-decoration: none;
            padding: 0.5rem 1.1rem;
            border-radius: 999px;
            border: 1px solid #4b5563;
            color: #e5e7eb;
            font-size: 0.9rem;
            display: inline-block;
        }

        .btn-secondary-link:hover {
            border-color: #9ca3af;
        }

        .message {
            margin-top: 0.8rem;
            font-size: 0.9rem;
            color: #e5e7eb;
        }

        footer {
            margin-top: 3rem;
            padding: 1.5rem 2rem;
            font-size: 0.8rem;
            color: #6b7280;
            border-top: 1px solid #1f2937;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">The Owls Net</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="feed.php">Feed</a>
        <a href="profile.php">Profile</a>
        <a href="settings.php">Settings</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Edit profile</h1>
    <p class="subtitle">Upload a profile picture and update your About section.</p>

    <section class="card">
        <form action="editprofile.php" method="POST" enctype="multipart/form-data">
            <label>Profile picture</label>

            <div class="current-picture">
                <?php if ($currentPicture): ?>
                    Current picture:
                    <img src="<?php echo $currentPicture; ?>" alt="Profile picture">
                <?php else: ?>
                    No profile picture set.
                <?php endif; ?>
            </div>

            <input type="file" name="profile_picture" accept="image/*">

            <label for="bio">About</label>
            <textarea id="bio" name="bio" placeholder="Write a short bio about yourself..."><?php echo htmlspecialchars($currentBio); ?></textarea>

            <div class="btn-row">
                <a href="profile.php" class="btn-secondary-link">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>

            <?php if ($msg !== ''): ?>
                <div class="message"><?php echo $msg; ?></div>
            <?php endif; ?>
        </form>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 · Edit Profile
</footer>

</body>
</html>