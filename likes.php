<?php
require_once 'DB.php';

$postId = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$post = null;
$likes = [];
$likesCount = 0;

if (isset($conn) && !$conn->connect_error) {
    if ($postId > 0) {
        $sqlPost = "SELECT p.post_id, p.body_txt, p.created_at, u.username FROM posts p JOIN users u ON p.author_id = u.user_id WHERE p.post_id = $postId LIMIT 1";
        $resPost = $conn->query($sqlPost);

        if ($resPost && $resPost->num_rows === 1) {
            $post = $resPost->fetch_assoc();
        }

        $sqlLikes = "SELECT u.username, u.email, l.created_at FROM likes l JOIN users u ON l.user_id = u.user_id WHERE l.post_id = $postId ORDER BY l.created_at DESC";
        $resLikes = $conn->query($sqlLikes);

        if ($resLikes) {
            while ($row = $resLikes->fetch_assoc()) {
                $likes[] = $row;
            }
        }

        $likesCount = count($likes);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Likes</title>
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

        .post-card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
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
            margin-top: 0.35rem;
        }

        .likes-card {
            background: #020617;
            border-radius: 12px;
            border: 1px solid #1f2937;
            padding: 1rem 1.25rem;
        }

        .likes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .likes-count {
            font-weight: 600;
        }

        .likes-list {
            list-style: none;
            padding-left: 0;
            margin: 0;
        }

        .like-item {
            padding: 0.6rem 0;
            border-top: 1px solid #111827;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .like-user {
            font-size: 0.95rem;
        }

        .like-username {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        .like-meta {
            font-size: 0.8rem;
            color: #9ca3af;
        }

        footer {
            margin-top: 3rem;
            padding: 1.5rem 2rem;
            font-size: 0.8rem;
            color: #6b7280;
            border-top: 1px solid #1f2937;
            text-align: center;
        }

        .back-link {
            font-size: 0.9rem;
            color: #38bdf8;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
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
    <a class="back-link" href="feed.php">← Back to feed</a>

    <h1 class="page-title">Likes on this post</h1>

    <?php if ($post): ?>
    <section class="post-card">
        <div class="post-header">
            <div>
                <div class="post-author"><?php echo $post['username']; ?></div>
                <div class="post-username"><?php echo '@' . $post['username']; ?></div>
            </div>
            <div class="post-time"><?php echo $post['created_at']; ?></div>
        </div>
        <p class="post-body">
            <?php echo $post['body_txt']; ?>
        </p>
    </section>
    <?php else: ?>
    <p class="page-subtitle">
        Post not found.
    </p>
    <?php endif; ?>

    <section class="likes-card">
        <div class="likes-header">
            <div class="likes-count"><?php echo $likesCount; ?> like(s)</div>
        </div>

        <?php if ($likesCount === 0): ?>
            <p style="font-size:0.9rem; color:#9ca3af;">
                No likes yet for this post.
            </p>
        <?php else: ?>
            <ul class="likes-list">
                <?php foreach ($likes as $l): ?>
                    <li class="like-item">
                        <div>
                            <div class="like-user"><?php echo $l['username']; ?></div>
                            <div class="like-username"><?php echo '@' . $l['username']; ?></div>
                        </div>
                        <div class="like-meta">
                            Liked · <?php echo $l['created_at']; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</main>

<footer>
    The Owls Net · CSC 335 · Likes Page Prototype
</footer>

</body>
</html>
