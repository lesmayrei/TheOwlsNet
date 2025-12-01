<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'DB.php';
session_start();

$userId = 0;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

$postError = '';
$posts = [];
$postsCount = 0;
$searchResults = [];

if (!empty($conn) && !$conn->connect_error) {

    /* -------------------------
       HANDLE POST REQUESTS
    -------------------------- */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId > 0) {

        $formType = $_POST['form_type'] ?? 'new_post';

        // create a new post
        if ($formType === 'new_post') {
            $body = trim($_POST['post_body'] ?? '');

            if ($body === '') {
                $postError = 'Post cannot be empty.';
            } else {
                $bodyEsc = $conn->real_escape_string($body);
                $sqlInsert = "
                    INSERT INTO posts (author_id, body_txt, created_at)
                    VALUES ($userId, '$bodyEsc', NOW())
                ";
                $conn->query($sqlInsert);
                header("Location: feed.php");
                exit();
            }
        }

        // like / unlike a post
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

            header("Location: feed.php");
            exit();
        }

        // add a comment
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

            header("Location: feed.php");
            exit();
        }
    }

    /* -------------------------
       LOAD FEED POSTS
       (only people I follow + myself)
    -------------------------- */
    $sql = "
        SELECT 
            p.post_id,
            p.author_id,
            p.body_txt,
            p.created_at,
            u.username
        FROM posts p
        JOIN users u ON p.author_id = u.user_id
        LEFT JOIN profiles pr ON pr.user_id = u.user_id
        LEFT JOIN follows f ON f.profile_id = pr.profile_id
        WHERE (f.user_id = $userId) OR (p.author_id = $userId)
        ORDER BY p.created_at DESC
    ";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $posts[] = $r;
        }
    }
    $postsCount = count($posts);

    /* -------------------------
       USER SEARCH BY USERNAME
    -------------------------- */
    if (isset($_GET['search_user']) && trim($_GET['search_user']) !== '') {
        $term = $conn->real_escape_string(trim($_GET['search_user']));
        $sqlUsers = "
            SELECT user_id, username
            FROM users
            WHERE username LIKE '%$term%'
            ORDER BY username ASC
        ";
        $rsUsers = $conn->query($sqlUsers);
        if ($rsUsers) {
            while ($u = $rsUsers->fetch_assoc()) {
                $searchResults[] = $u;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feed – The Owls Net</title>
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

        .page-title {
            font-size: 1.7rem;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            font-size: 0.95rem;
            color: #9ca3af;
            margin-bottom: 1.5rem;
        }

        .new-post-card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .new-post-card textarea {
            width: 100%;
            min-height: 80px;
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: #f9fafb;
            resize: vertical;
            font-family: inherit;
            font-size: 0.95rem;
        }

        .new-post-card textarea:focus {
            outline: none;
            border-color: #38bdf8;
        }

        .new-post-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 0.75rem;
        }

        .btn {
            padding: 0.45rem 1rem;
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
            margin: 0.35rem 0 0.75rem 0;
        }

        .post-footer {
            font-size: 0.85rem;
            color: #9ca3af;
            display: flex;
            gap: 1rem;
            align-items: center;
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
            padding: 1.5rem;
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
    <!-- USER SEARCH BAR -->
    <section style="margin-bottom:1.5rem;">
        <form action="feed.php" method="GET" style="display:flex; gap:0.5rem;">
            <input
                type="text"
                name="search_user"
                placeholder="Search users by username..."
                value="<?php echo isset($_GET['search_user']) ? htmlspecialchars($_GET['search_user']) : ''; ?>"
                style="flex:1; padding:0.5rem; border-radius:8px; border:1px solid #475569; background:#0f172a; color:#f9fafb;"
            >
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if (!empty($searchResults)): ?>
            <div style="margin-top:1rem; background:#020617; padding:1rem; border-radius:8px; border:1px solid #1f2937;">
                <h3 style="margin:0 0 0.5rem 0;">Search Results</h3>
                <ul style="list-style:none; padding-left:0; margin:0;">
                    <?php foreach ($searchResults as $u): ?>
                        <li style="padding:0.3rem 0;">
                            <a href="profile.php?user_id=<?php echo $u['user_id']; ?>" style="color:#38bdf8; text-decoration:none;">
                                @<?php echo htmlspecialchars($u['username']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php elseif (isset($_GET['search_user'])): ?>
            <p style="margin-top:1rem; font-size:0.9rem; color:#9ca3af;">No users found.</p>
        <?php endif; ?>
    </section>

    <h1 class="page-title">Campus Feed</h1>
    <p class="page-subtitle">
        See what other students are posting right now.
    </p>

    <!-- NEW POST -->
    <section class="new-post-card">
        <form action="feed.php" method="POST">
            <input type="hidden" name="form_type" value="new_post">
            <label for="post_body" style="font-size:0.9rem; color:#cbd5e1; display:block; margin-bottom:0.3rem;">
                Share something with your campus:
            </label>
            <textarea id="post_body" name="post_body" placeholder="What's on your mind?"></textarea>

            <?php if ($postError !== ''): ?>
                <p style="margin-top:0.5rem; font-size:0.85rem; color:#fca5a5;">
                    <?php echo $postError; ?>
                </p>
            <?php endif; ?>

            <div class="new-post-actions">
                <button class="btn btn-primary" type="submit">Post</button>
            </div>
        </form>
    </section>

    <!-- FEED POSTS -->
    <section>
        <?php if ($postsCount === 0): ?>
            <p style="font-size:0.9rem; color:#9ca3af;">
                No posts yet. Once people start posting, they will show up here.
            </p>
        <?php else: ?>
            <ul class="posts-list">
                <?php foreach ($posts as $p): ?>
                    <?php
                        $postId   = (int)$p['post_id'];
                        $authorId = (int)$p['author_id'];

                        // likes
                        $likesCount = 0;
                        $userLiked = false;

                        if (!empty($conn) && !$conn->connect_error) {
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

                        // comments
                        $comments = [];
                        if (!empty($conn) && !$conn->connect_error) {
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
                                <div class="post-author">
                                    <a href="profile.php?user_id=<?php echo $authorId; ?>" style="color:#f9fafb; text-decoration:none;">
                                        <?php echo htmlspecialchars($p['username']); ?>
                                    </a>
                                </div>
                                <div class="post-username">
                                    <a href="profile.php?user_id=<?php echo $authorId; ?>" style="color:#38bdf8; text-decoration:none;">
                                        @<?php echo htmlspecialchars($p['username']); ?>
                                    </a>
                                </div>
                            </div>
                            <div class="post-time"><?php echo $p['created_at']; ?></div>
                        </div>

                        <p class="post-body">
                            <?php echo $p['body_txt']; ?>
                        </p>

                        <div class="post-footer">
                            <form action="feed.php" method="POST" style="display:inline;">
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

                            <form action="feed.php" method="POST" style="margin-top:0.3rem; display:flex; gap:0.4rem;">
                                <input type="hidden" name="form_type" value="comment">
                                <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                <input
                                    type="text"
                                    name="comment_body"
                                    placeholder="Add a comment..."
                                    style="flex:1; padding:0.3rem; border-radius:6px; border:1px solid #475569; background:#020617; color:#f9fafb;"
                                >
                                <button type="submit" class="btn-primary" style="border-radius:6px; border:none; padding:0.3rem 0.7rem; font-size:0.8rem;">
                                    Post
                                </button>
                            </form>
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