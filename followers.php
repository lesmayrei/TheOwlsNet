<?php
session_start();
require_once 'DB.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = (int)$_SESSION['user_id'];
$followers = [];

if (isset($conn) && !$conn->connect_error) {
    $profileId = null;

    $resProfile = $conn->query("SELECT profile_id FROM profiles WHERE user_id = $userId LIMIT 1");
    if ($resProfile && $resProfile->num_rows === 1) {
        $rowP = $resProfile->fetch_assoc();
        $profileId = (int)$rowP['profile_id'];
    }

    if ($profileId !== null) {
        $sqlFollowers = "
            SELECT u.user_id, u.username, u.email, f.created_at
            FROM follows f
            JOIN users u ON f.user_id = u.user_id
            WHERE f.profile_id = $profileId
            ORDER BY f.created_at DESC
        ";
        $resFollowers = $conn->query($sqlFollowers);
        if ($resFollowers) {
            while ($row = $resFollowers->fetch_assoc()) {
                $followers[] = $row;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your followers – The Owls Net</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            background: #0f172a;
            color: #f9fafb;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
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
            max-width: 800px;
            margin: 2rem auto 3rem auto;
            padding: 0 1.5rem;
        }
        h1 {
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
        }
        p.subtitle {
            color: #9ca3af;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        .card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1.25rem 1.5rem;
        }
        ul.follow-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }
        li.follow-item {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 0.6rem 0;
            border-bottom: 1px solid #1f2937;
        }
        li.follow-item:last-child {
            border-bottom: none;
        }
        .follow-main {
            display: flex;
            flex-direction: column;
        }
        .follow-username a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
        }
        .follow-username a:hover {
            text-decoration: underline;
        }
        .follow-email {
            color: #9ca3af;
            font-size: 0.85rem;
        }
        .follow-time {
            color: #6b7280;
            font-size: 0.8rem;
            margin-left: 1rem;
            white-space: nowrap;
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
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main>
    <h1>Your followers</h1>
    <p class="subtitle">
        These accounts are currently following you.
    </p>

    <section class="card">
        <?php if (empty($followers)): ?>
            <p style="color:#9ca3af; font-size:0.95rem;">
                You do not have any followers yet.
            </p>
        <?php else: ?>
            <ul class="follow-list">
                <?php foreach ($followers as $f): ?>
                    <li class="follow-item">
                        <div class="follow-main">
                            <div class="follow-username">
                                <a href="profile.php?user_id=<?php echo (int)$f['user_id']; ?>">
                                    @<?php echo htmlspecialchars($f['username']); ?>
                                </a>
                            </div>
                            <div class="follow-email">
                                <?php echo htmlspecialchars($f['email']); ?>
                            </div>
                        </div>
                        <div class="follow-time">
                            <?php echo $f['created_at']; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<footer>
    The Owls Net · CSC 335
</footer>
</body>
</html>