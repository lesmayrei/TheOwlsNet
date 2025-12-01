<?php
session_start();
require_once 'DB.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// whose profile are we viewing? (default to yourself)
$viewedUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;
if ($viewedUserId <= 0) {
    $viewedUserId = $userId;
}

$name = 'Student Name';
$handle = '@username';
$email = 'yourname@southernct.edu';
$joinedAt = '';
$followersCount = 0;
$followingCount = 0;
$totalPosts = 0;
$recentPosts = [];
$picture = '';
$profileId = null;
$profileVisibility = 'public';
$isFollowing = false;

// handle follow / unfollow actions
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['follow_action']) &&
    $userId > 0 &&
    $viewedUserId > 0 &&
    $userId !== $viewedUserId &&
    isset($conn) && !$conn->connect_error
) {
    // find the profile_id for the viewed user
    $targetProfileId = null;
    $sqlFindProfile = "SELECT profile_id FROM profiles WHERE user_id = $viewedUserId LIMIT 1";
    $resFindProfile = $conn->query($sqlFindProfile);
    if ($resFindProfile && $resFindProfile->num_rows === 1) {
        $rowProf = $resFindProfile->fetch_assoc();
        $targetProfileId = (int)$rowProf['profile_id'];
    }

    if ($targetProfileId !== null) {
        if ($_POST['follow_action'] === 'follow') {
            $stmt = $conn->prepare("INSERT INTO follows (user_id, profile_id) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ii", $userId, $targetProfileId);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($_POST['follow_action'] === 'unfollow') {
            $stmt = $conn->prepare("DELETE FROM follows WHERE user_id = ? AND profile_id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $userId, $targetProfileId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    // avoid resubmitting the form on refresh
    header("Location: profile.php?user_id=" . $viewedUserId);
    exit();
}

// handle like / comment actions on this profile's posts
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['form_type']) &&
    $userId > 0 &&
    isset($conn) && !$conn->connect_error
) {
    $formType = $_POST['form_type'];

    if ($formType === 'like' && isset($_POST['post_id'], $_POST['like_action'])) {
        $postId = (int)$_POST['post_id'];
        $action = $_POST['like_action'];

        if ($action === 'like') {
            $conn->query("
                INSERT IGNORE INTO likes (user_id, post_id, created_at)
                VALUES ($userId, $postId, NOW())
            ");
        } elseif ($action === 'unlike') {
            $conn->query("
                DELETE FROM likes
                WHERE user_id = $userId AND post_id = $postId
            ");
        }

        header("Location: profile.php?user_id=" . $viewedUserId);
        exit();
    }

    if ($formType === 'comment' && isset($_POST['post_id'])) {
        $postId = (int)$_POST['post_id'];
        $commentBody = trim($_POST['comment_body'] ?? '');

        if ($commentBody !== '') {
            $commentBodyEsc = $conn->real_escape_string($commentBody);
            $conn->query("
                INSERT INTO comments (user_id, post_id, body_txt, created_at)
                VALUES ($userId, $postId, '$commentBodyEsc', NOW())
            ");
        }

        header("Location: profile.php?user_id=" . $viewedUserId);
        exit();
    }
}

if ($viewedUserId > 0 && isset($conn) && !$conn->connect_error) {
    $sqlUser = "
        SELECT email, username, created_at, status
        FROM users
        WHERE user_id = $viewedUserId
        LIMIT 1
    ";
    $resUser = $conn->query($sqlUser);

    if ($resUser && $resUser->num_rows === 1) {
        $u = $resUser->fetch_assoc();

        // if this account is not active, show a deactivated message and stop
        if ($u['status'] !== 'active') {
            echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Account deactivated – The Owls Net</title>
    <style>
        body {
            background: #0f172a;
            color: #f9fafb;
            font-family: system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            background: #020617;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #1f2937;
            max-width: 420px;
            text-align: center;
        }
        h1 {
            margin: 0 0 0.5rem 0;
        }
        p {
            color: #9ca3af;
            font-size: 0.95rem;
        }
        a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 0.9rem;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class='box'>
        <h1>Account deactivated</h1>
        <p>This account has been deactivated and is not currently visible on The Owls Net.</p>
        <p><a href='index.php'>Return to home</a></p>
    </div>
</body>
</html>";
            exit();
        }

        $email = $u['email'];
        $name = $u['username'];
        $handle = '@' . $u['username'];
        $joinedAt = $u['created_at'];
    } else {
        // no such user, show a simple not-found message and stop
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Profile not found – The Owls Net</title>
    <style>
        body {
            background: #0f172a;
            color: #f9fafb;
            font-family: system-ui, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .box {
            background: #020617;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #1f2937;
            max-width: 420px;
            text-align: center;
        }
        h1 {
            margin: 0 0 0.5rem 0;
        }
        p {
            color: #9ca3af;
            font-size: 0.95rem;
        }
        a {
            color: #38bdf8;
            text-decoration: none;
            font-size: 0.9rem;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class='box'>
        <h1>Profile not found</h1>
        <p>The profile you are trying to view does not exist.</p>
        <p><a href='index.php'>Return to home</a></p>
    </div>
</body>
</html>";
        exit();
    }

    $sqlProfile = "SELECT profile_id, picture, profile_visibility FROM profiles WHERE user_id = $viewedUserId LIMIT 1";
    $resProfile = $conn->query($sqlProfile);

    if ($resProfile && $resProfile->num_rows === 1) {
        $p = $resProfile->fetch_assoc();
        $profileId = (int)$p['profile_id'];
        if (isset($p['picture'])) {
            $picture = $p['picture'];
        }
        if (isset($p['profile_visibility']) && $p['profile_visibility'] !== '') {
            $profileVisibility = $p['profile_visibility'];
        }

        // followers count for this profile
        $sqlFollowersCount = "SELECT COUNT(*) AS c FROM follows WHERE profile_id = $profileId";
        $resFollowersCount = $conn->query($sqlFollowersCount);
        if ($resFollowersCount) {
            $rowF = $resFollowersCount->fetch_assoc();
            $followersCount = (int)$rowF['c'];
        }

        // is the logged-in user following this profile?
        if ($userId > 0 && $userId !== $viewedUserId) {
            $sqlIsFollowing = "SELECT 1 FROM follows WHERE user_id = $userId AND profile_id = $profileId LIMIT 1";
            $resIsFollowing = $conn->query($sqlIsFollowing);
            if ($resIsFollowing && $resIsFollowing->num_rows === 1) {
                $isFollowing = true;
            }
        }
    }

    $sqlFollowingCount = "SELECT COUNT(*) AS c FROM follows WHERE user_id = $viewedUserId";
    $resFollowingCount = $conn->query($sqlFollowingCount);
    if ($resFollowingCount) {
        $rowFo = $resFollowingCount->fetch_assoc();
        $followingCount = (int)$rowFo['c'];
    }

    $sqlPostsCount = "SELECT COUNT(*) AS c FROM posts WHERE author_id = $viewedUserId AND (deleted_at IS NULL)";
    $resPostsCount = $conn->query($sqlPostsCount);
    if ($resPostsCount) {
        $rowP = $resPostsCount->fetch_assoc();
        $totalPosts = (int)$rowP['c'];
    }

    $sqlRecent = "SELECT post_id, body_txt, created_at FROM posts WHERE author_id = $viewedUserId AND (deleted_at IS NULL) ORDER BY created_at DESC LIMIT 5";
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
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid #38bdf8;
            margin-bottom: 0.75rem;
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

        .post-card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }

        .post-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.4rem;
        }

        .post-author {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .post-username {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .post-time {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .post-body {
            font-size: 0.95rem;
            margin: 0.35rem 0 0 0;
        }

        .post-footer {
            font-size: 0.85rem;
            color: #9ca3af;
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-top: 0.5rem;
        }

        .post-footer button {
            background: transparent;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            font-size: 0.85rem;
        }

        .post-footer button:hover {
            color: #e5e7eb;
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
            <?php if ($picture): ?>
                <img src="<?php echo $picture; ?>" alt="Profile picture" class="profile-avatar">
            <?php endif; ?>
            <h1 class="profile-name"><?php echo $name; ?></h1>
            <div class="profile-username"><?php echo $handle; ?></div>
            <div class="profile-email"><?php echo $email; ?></div>
            <div class="profile-actions">
                <?php if ($userId > 0 && $userId === $viewedUserId): ?>
                    <a href="editprofile.php" class="btn">Edit profile</a>
                <?php elseif ($userId > 0 && $profileId !== null): ?>
                    <form action="profile.php?user_id=<?php echo $viewedUserId; ?>" method="POST" style="display:inline;">
                        <input type="hidden" name="follow_action" value="<?php echo $isFollowing ? 'unfollow' : 'follow'; ?>">
                        <button type="submit" class="btn">
                            <?php echo $isFollowing ? 'Unfollow' : 'Follow'; ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <aside class="stats-card">
            <div class="stats-row">
                <span>Followers</span>
                <span>
                    <a href="followers.php?user_id=<?php echo $viewedUserId; ?>" style="color:#38bdf8; text-decoration:none;">
                        <?php echo $followersCount; ?>
                    </a>
                </span>
            </div>
            <div class="stats-row">
                <span>Following</span>
                <span>
                    <a href="following.php?user_id=<?php echo $viewedUserId; ?>" style="color:#38bdf8; text-decoration:none;">
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

    <!-- Recent posts section -->
    <section class="section">
        <h2>Recent posts</h2>
        <?php
        $canViewPosts = !($profileVisibility === 'private' && $userId !== $viewedUserId && !$isFollowing);
        ?>
        <?php if (!$canViewPosts): ?>
            <p style="color:#9ca3af; font-size:0.9rem;">
                This profile is private. Follow to see their posts.
            </p>
        <?php else: ?>
            <?php if (empty($recentPosts)): ?>
                <p style="color:#9ca3af; font-size:0.9rem;">
                    <?php echo ($viewedUserId === $userId) ? "You don't have any posts yet." : "This user hasn't posted yet."; ?>
                </p>
            <?php else: ?>
            <ul class="posts-list">
                <?php foreach ($recentPosts as $post): ?>
                    <?php
                        $postId = (int)$post['post_id'];

                        // likes for this post
                        $likesCount = 0;
                        $userLiked = false;

                        if (isset($conn) && !$conn->connect_error) {
                            $resLikesCount = $conn->query("SELECT COUNT(*) AS c FROM likes WHERE post_id = $postId");
                            if ($resLikesCount) {
                                $rowLc = $resLikesCount->fetch_assoc();
                                $likesCount = (int)$rowLc['c'];
                            }

                            $resUserLiked = $conn->query("
                                SELECT 1 FROM likes
                                WHERE user_id = $userId AND post_id = $postId
                                LIMIT 1
                            ");
                            if ($resUserLiked && $resUserLiked->num_rows === 1) {
                                $userLiked = true;
                            }
                        }

                        // comments for this post
                        $comments = [];
                        if (isset($conn) && !$conn->connect_error) {
                            $resComments = $conn->query("
                                SELECT c.body_txt, c.created_at, u.username
                                FROM comments c
                                JOIN users u ON c.user_id = u.user_id
                                WHERE c.post_id = $postId
                                ORDER BY c.created_at ASC
                            ");
                            if ($resComments) {
                                while ($cRow = $resComments->fetch_assoc()) {
                                    $comments[] = $cRow;
                                }
                            }
                        }
                    ?>
                    <li class="post-card">
                        <div class="post-header">
                            <div>
                                <div class="post-author"><?php echo htmlspecialchars($name); ?></div>
                                <div class="post-username"><?php echo htmlspecialchars($handle); ?></div>
                            </div>
                            <div class="post-time"><?php echo $post['created_at']; ?></div>
                        </div>
                        <p class="post-body">
                            <?php echo $post['body_txt']; ?>
                        </p>

                        <div class="post-footer">
                            <form action="profile.php?user_id=<?php echo $viewedUserId; ?>" method="POST" style="display:inline;">
                                <input type="hidden" name="form_type" value="like">
                                <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                <input type="hidden" name="like_action" value="<?php echo $userLiked ? 'unlike' : 'like'; ?>">
                                <button type="submit">
                                    <?php echo $userLiked ? 'Unlike' : 'Like'; ?>
                                </button>
                            </form>
                            <span>
                                <?php echo $likesCount; ?> like<?php echo $likesCount === 1 ? '' : 's'; ?>
                            </span>
                        </div>

                        <div style="margin-top:0.5rem; padding-left:0.5rem; border-left:1px solid #1f2937;">
                            <?php if (empty($comments)): ?>
                                <p style="font-size:0.8rem; color:#6b7280; margin:0 0 0.3rem 0;">
                                    No comments yet.
                                </p>
                            <?php else: ?>
                                <?php foreach ($comments as $c): ?>
                                    <div style="margin-bottom:0.35rem;">
                                        <span style="font-size:0.8rem; color:#9ca3af;">
                                            <strong>@<?php echo htmlspecialchars($c['username']); ?></strong>
                                            · <?php echo $c['created_at']; ?>
                                        </span>
                                        <div style="font-size:0.9rem;">
                                            <?php echo htmlspecialchars($c['body_txt']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <form action="profile.php?user_id=<?php echo $viewedUserId; ?>" method="POST" style="margin-top:0.3rem; display:flex; gap:0.4rem;">
                                <input type="hidden" name="form_type" value="comment">
                                <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                <input
                                    type="text"
                                    name="comment_body"
                                    placeholder="Add a comment..."
                                    style="flex:1; padding:0.3rem; border-radius:6px; border:1px solid #475569; background:#020617; color:#f9fafb;"
                                >
                                <button type="submit" class="btn" style="border:none; background:#38bdf8; color:#020617; padding:0.3rem 0.7rem; font-size:0.8rem;">
                                    Post
                                </button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 · Profile Page Prototype
</footer>

</body>
</html>
