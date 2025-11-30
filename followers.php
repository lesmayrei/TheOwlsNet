<?php
// Include database connection
require_once 'DB.php'; // assumes DB.php creates $conn (mysqli)

// Get user_id from the URL, e.g. followers.php?user_id=1
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;

// Prepare variables
$userName = "Student Name";
$userUsername = "@username";
$followersCount = 0;
$followers = [];

// Only proceed if we have a DB connection
if (isset($conn) && !$conn->connect_error) {

    // 1. Get basic user/profile info
    $sqlUser = "
        SELECT u.user_id, u.username, u.email, p.profile_id
        FROM users u
        JOIN profiles p ON u.user_id = p.user_id
        WHERE u.user_id = $userId
        LIMIT 1
    ";
    $resultUser = $conn->query($sqlUser);

    $profileId = null;

    if ($resultUser && $resultUser->num_rows === 1) {
        $row = $resultUser->fetch_assoc();
        $userName = $row['username'];          // you can change this to a full name later
        $userUsername = '@' . $row['username'];
        $profileId = (int)$row['profile_id'];
    }

    // 2. If we found a profile, count how many followers it has
    if ($profileId !== null) {
        $sqlCount = "
            SELECT COUNT(*) AS follower_count
            FROM follows
            WHERE profile_id = $profileId
        ";
        $resultCount = $conn->query($sqlCount);
        if ($resultCount && $row = $resultCount->fetch_assoc()) {
            $followersCount = (int)$row['follower_count'];
        }

        // 3. Get the list of followers (users who follow this profile)
        $sqlFollowers = "
            SELECT u.user_id, u.username, u.email
            FROM follows f
            JOIN users u ON f.user_id = u.user_id  -- follower user
            WHERE f.profile_id = $profileId
            ORDER BY u.username
        ";
        $resultFollowers = $conn->query($sqlFollowers);

        if ($resultFollowers) {
            while ($row = $resultFollowers->fetch_assoc()) {
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
    <title>Followers – The Owls Net</title>
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
            max-width: 720px;
            margin: 2rem auto 3rem auto;
            padding: 0 1.5rem;
        }

        .page-title {
            font-size: 1.6rem;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 0.95rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .user-summary {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        .user-summary-name {
            font-weight: 600;
        }

        .user-summary-username {
            font-size: 0.85rem;
            color: #9ca3af;
        }

        .followers-card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
        }

        .followers-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .followers-count {
            font-weight: 600;
        }

        .followers-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .follower-item {
            padding: 0.6rem 0;
            border-top: 1px solid #111827;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .follower-main {
            display: flex;
            flex-direction: column;
        }

        .follower-name {
            font-size: 0.95rem;
        }

        .follower-username {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .follower-meta {
            font-size: 0.8rem;
            color: #9ca3af;
            text-align: right;
        }

        .back-link {
            font-size: 0.9rem;
            color: #38bdf8;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
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
        <a href="index.html">Home</a>
        <a href="feed.html">Feed</a>
        <a href="profile.html">Profile</a>
        <a href="login.html">Logout</a>
    </nav>
</header>

<main>
    <a class="back-link" href="profile.html">← Back to profile</a>

    <h1 class="page-title">Followers</h1>
    <p class="page-subtitle">
        This page lists the users who follow this account.
    </p>

    <!-- Summary of whose followers we are viewing -->
    <section class="user-summary">
        <div class="user-summary-name">
            <?php echo htmlspecialchars($userName); ?>
        </div>
        <div class="user-summary-username">
            <?php echo htmlspecialchars($userUsername); ?>
        </div>
        <div style="margin-top:0.4rem; color:#cbd5e1; font-size:0.9rem;">
            You currently have <strong><?php echo $followersCount; ?></strong> follower(s).
        </div>
    </section>

    <!-- Followers list -->
    <section class="followers-card">
        <div class="followers-header">
            <div class="followers-count">
                <?php echo $followersCount; ?> follower(s)
            </div>
            <div style="font-size:0.8rem; color:#9ca3af;">Loaded from database</div>
        </div>

        <?php if ($followersCount === 0): ?>
            <p style="font-size:0.9rem; color:#9ca3af;">
                No followers yet.
            </p>
        <?php else: ?>
            <ul class="followers-list">
                <?php foreach ($followers as $f): ?>
                    <li class="follower-item">
                        <div class="follower-main">
                            <div class="follower-name">
                                <?php echo htmlspecialchars($f['username']); ?>
                            </div>
                            <div class="follower-username">
                                <?php echo '@' . htmlspecialchars($f['username']); ?>
                            </div>
                        </div>
                        <div class="follower-meta">
                            <?php echo htmlspecialchars($f['email']); ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 · Followers Page (PHP)
</footer>

</body>
</html>
