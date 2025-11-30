<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'DB.php';
session_start();

$userId = 1;
if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
}

$postError = '';
$posts = [];
$postsCount = 0;

if (!empty($conn) && !$conn->connect_error) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = '';
        if (isset($_POST['post_body'])) {
            $body = trim($_POST['post_body']);
        }

        if ($body === '') {
            $postError = 'Post cannot be empty.';
        } else {
            $bodyEsc = $conn->real_escape_string($body);
            $insertSql = "INSERT INTO posts (author_id, body_txt, created_at) VALUES ($userId, '$bodyEsc', NOW())";
            $conn->query($insertSql);
            header('Location: feed.php');
            exit;
        }
    }

    $sql = "SELECT p.post_id, p.body_txt, p.created_at, u.username FROM posts p JOIN users u ON p.author_id = u.user_id ORDER BY p.created_at DESC";
    $res = $conn->query($sql);

    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $posts[] = $r;
        }
    }

    $postsCount = count($posts);
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
        <a href="index.html">Home</a>
        <a href="feed.php">Feed</a>
        <a href="profile.html">Profile</a>
        <a href="login.html">Logout</a>
    </nav>
</header>

<main>
    <h1 class="page-title">Campus Feed</h1>
    <p class="page-subtitle">
        See what other students are posting right now.
    </p>

    <section class="new-post-card">
        <form action="feed.php" method="POST">
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

    <section>
        <?php if ($postsCount === 0): ?>
            <p style="font-size:0.9rem; color:#9ca3af;">
                No posts yet. Once people start posting, they will show up here.
            </p>
        <?php else: ?>
            <ul class="posts-list">
                <?php foreach ($posts as $p): ?>
                    <li class="post-card">
                        <div class="post-header">
                            <div>
                                <div class="post-author"><?php echo $p['username']; ?></div>
                                <div class="post-username"><?php echo '@' . $p['username']; ?></div>
                            </div>
                            <div class="post-time"><?php echo $p['created_at']; ?></div>
                        </div>
                        <p class="post-body">
                            <?php echo $p['body_txt']; ?></p>
                        <div class="post-footer">
                            <button type="button">Like</button>
                            <button type="button">Comments</button>
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
