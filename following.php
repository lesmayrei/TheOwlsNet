```php
<?php
require_once 'DB.php';

$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
$displayName = 'User';
$atName = '@user';
$following = [];
$followingCount = 0;

if (!empty($conn) && !$conn->connect_error) {
    $userSql = "SELECT u.username, p.profile_id FROM users u JOIN profiles p ON u.user_id = p.user_id WHERE u.user_id = $userId LIMIT 1";
    $userRes = $conn->query($userSql);

    if ($userRes && $userRes->num_rows > 0) {
        $u = $userRes->fetch_assoc();
        $displayName = $u['username'];
        $atName = '@' . $u['username'];
        $profileId = (int)$u['profile_id'];

        $followSql = "SELECT u.username, u.email FROM follows f JOIN profiles p ON f.profile_id = p.profile_id JOIN users u ON p.user_id = u.user_id WHERE f.user_id = $userId ORDER BY u.username";
        $followRes = $conn->query($followSql);

        if ($followRes) {
            while ($row = $followRes->fetch_assoc()) {
                $following[] = $row;
            }
        }

        $followingCount = count($following);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Following</title>
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

        .following-card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
        }

        .following-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .following-count {
            font-weight: 600;
        }

        .following-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .following-item {
            padding: 0.6rem 0;
            border-top: 1px solid #111827;
            display: flex;
            align-items: baseline;
        }

        .following-main {
            display: flex;
            flex-direction: column;
        }

        .following-name {
            font-size: 0.95rem;
        }

        .following-username {
            font-size: 0.8rem;
            color: #9ca3af;
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
        <a href="index.php">Home</a>
        <a href="feed.html">Feed</a>
        <a href="profile.html">Profile</a>
        <a href="login.html">Logout</a>
    </nav>
</header>

<main>
    <a class="back-link" href="profile.html">← Back to profile</a>

    <h1 class="page-title">People you follow</h1>
    <p class="page-subtitle">Accounts you are following.</p>

    <section class="user-summary">
        <div class="user-summary-name">
            <?php echo $displayName; ?>
        </div>
        <div class="user-summary-username">
            <?php echo $atName; ?>
        </div>
        <div style="margin-top:0.4rem; color:#cbd5e1; font-size:0.9rem;">
            You are following <strong><?php echo $followingCount; ?></strong> account(s).
        </div>
    </section>

    <section class="following-card">
        <div class="following-header">
            <div class="following-count">
                Following (<?php echo $followingCount; ?>)
            </div>
        </div>

        <?php if ($followingCount <= 0): ?>
            <p style="font-size:0.9rem; color:#9ca3af;">
                Following 0 pages.
            </p>
        <?php else: ?>
            <ul class="following-list">
                <?php foreach ($following as $p): ?>
                <li class="following-item">
                    <div class="following-main">
                        <div class="following-name"><?php echo $p['username']; ?></div>
                        <div class="following-username"><?php echo '@' . $p['username']; ?></div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 ·
</footer>

</body>
</html>
```