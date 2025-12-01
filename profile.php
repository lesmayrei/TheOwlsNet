<?php
session_start();
require_once 'DB.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$name = 'Student Name';
$handle = '@username';
$email = 'yourname@owls.southernct.edu';
$joinedAt = '';
$followersCount = 0;
$followingCount = 0;
$totalPosts = 0;
$recentPosts = [];
$about = '';

if ($userId > 0 && isset($conn) && !$conn->connect_error) {
    $sqlUser = "SELECT email, username, created_at FROM users WHERE user_id = $userId LIMIT 1";
    $resUser = $conn->query($sqlUser);

    if ($resUser && $resUser->num_rows === 1) {
        $u = $resUser->fetch_assoc();
        $email = $u['email'];
        $name = $u['username'];
        $handle = '@' . $u['username'];
        $joinedAt = $u['created_at'];
    }

    $sqlProfile = "SELECT profile_id, profile_status FROM profiles WHERE user_id = $userId LIMIT 1";
    $resProfile = $conn->query($sqlProfile);

    if ($resProfile && $resProfile->num_rows === 1) {
        $p = $resProfile->fetch_assoc();
        $profileId = (int)$p['profile_id'];
        if (isset($p['profile_status'])) {
            $about = $p['profile_status'];
        }

        $sqlFollowersCount = "SELECT COUNT(*) AS c FROM follows WHERE profile_id = $profileId";
        $resFollowersCount = $conn->query($sqlFollowersCount);
        if ($resFollowersCount) {
            $rowF = $resFollowersCount->fetch_assoc();
            $followersCount = (int)$rowF['c'];
        }
    }

    $sqlFollowingCount = "SELECT COUNT(*) AS c FROM follows WHERE user_id = $userId";
    $resFollowingCount = $conn->query($sqlFollowingCount);
    if ($resFollowingCount) {
        $rowFo = $resFollowingCount->fetch_assoc();
        $followingCount = (int)$rowFo['c'];
    }

    $sqlPostsCount = "SELECT COUNT(*) AS c FROM posts WHERE author_id = $userId AND (deleted_at IS NULL)";
    $resPostsCount = $conn->query($sqlPostsCount);
    if ($resPostsCount) {
        $rowP = $resPostsCount->fetch_assoc();
        $totalPosts = (int)$rowP['c'];
    }

    $sqlRecent = "SELECT body_txt, created_at FROM posts WHERE author_id = $userId AND (deleted_at IS NULL) ORDER BY created_at DESC LIMIT 5";
    $resRecent = $conn->query($sqlRecent);
    if ($resRecent) {
        while ($row = $resRecent->fetch_assoc()) {
            $recentPosts[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile – The Owls Net</title>
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
            max-width: 960px;
            margin: 2rem auto 3rem auto;
            padding: 0 1.5rem;
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .profile-main {
            flex: 2;
        }

        .profile-name {
            font-size: 1.8rem;
            margin: 0 0 0.25rem 0;
        }

        .profile-username {
            color: #9ca3af;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .profile-email {
            color: #cbd5e1;
            font-size: 0.9rem;
        }

        .profile-actions {
            margin-top: 1rem;
        }

        .btn {
            display: inline-block;
            padding: 0.45rem 1rem;
            border-radius: 999px;
            border: 1px solid #38bdf8;
            background: transparent;
            color: #e0f2fe;
            font-size: 0.9rem;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background: #0ea5e9;
            color: #0b1120;
        }

        .stats-card {
            flex: 1;
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
            font-size: 0.9rem;
        }

        .stats-title {
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }

        .section {
            margin-top: 2rem;
        }

        .section h2 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .section p {
            color: #cbd5e1;
            font-size: 0.95rem;
        }

        .posts-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .post-item {
            border-top: 1px solid #1f2937;
            padding: 0.9rem 0;
        }

        .post-meta {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 0.25rem;
        }

        .post-body {
            font-size: 0.95rem;
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
    <!-- Top section: basic profile info + stats -->
    <section class="profile-header">
        <div class="profile-main">
            <h1 class="profile-name"><?php echo $name; ?></h1>
            <div class="profile-username"><?php echo $handle; ?></div>
            <div class="profile-email"><?php echo $email; ?></div>
            <div class="profile-actions">
                <a href="editprofile.php" class="btn">Edit profile</a>
            </div>
        </div>

        <aside class="stats-card">
            <div class="stats-row">
                <span>Followers</span>
                <span>
                    <a href="followers.php?user_id=<?php echo $userId; ?>" style="color:#38bdf8; text-decoration:none;">
                        <?php echo $followersCount; ?>
                    </a>
                </span>
            </div>
            <div class="stats-row">
                <span>Following</span>
                <span>
                    <a href="following.php?user_id=<?php echo $userId; ?>" style="color:#38bdf8; text-decoration:none;">
                        <?php echo $followingCount; ?>
                    </a>
                </span>
            </div>
            <div class="stats-row">
                <span>Total posts</span>
                <span><?php echo $totalPosts; ?></span>
            </div>
            <div class="stats-row">
                <span>Joined</span>
                <span><?php echo $joinedAt !== '' ? $joinedAt : 'N/A'; ?></span>
            </div>
        </aside>
    </section>

    <!-- About section -->
    <section class="section">
        <h2>About</h2>
        <?php if ($about === '' || $about === null): ?>
            <p style="color:#9ca3af; font-size:0.9rem;">
                Use the Edit profile page to add a short bio.
            </p>
        <?php else: ?>
            <p style="font-size:0.95rem;">
                <?php echo nl2br(htmlspecialchars($about)); ?>
            </p>
        <?php endif; ?>
    </section>

    <!-- Recent posts section -->
    <section class="section">
        <h2>Recent posts</h2>
        <?php if (empty($recentPosts)): ?>
            <p style="color:#9ca3af; font-size:0.9rem;">You don't have any posts yet.</p>
        <?php else: ?>
        <ul class="posts-list">
            <?php foreach ($recentPosts as $post): ?>
                <li class="post-item">
                    <div class="post-meta"><?php echo $post['created_at']; ?></div>
                    <div class="post-body">
                        <?php echo $post['body_txt']; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 · Profile Page Prototype
</footer>

</body>
</html>
