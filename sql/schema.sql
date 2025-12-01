-- Create database
CREATE DATABASE IF NOT EXISTS owlsnet;
USE owlsnet;

-- USERS -------------------------------------------------
CREATE TABLE users (
    user_id        INT AUTO_INCREMENT PRIMARY KEY,
    email          VARCHAR(255) NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    username       VARCHAR(100) NOT NULL UNIQUE,
    status         VARCHAR(50),
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NULL,
    last_login_at  DATETIME NULL
);

-- PROFILES ---------------------------------------------
CREATE TABLE profiles (
    profile_id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id            INT NOT NULL UNIQUE,
    picture            VARCHAR(255),
    profile_status     VARCHAR(50),
    profile_visibility VARCHAR(50),
    follower_count     INT DEFAULT 0,
    following_count    INT DEFAULT 0,
    created_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- POSTS ------------------------------------------------
CREATE TABLE posts (
    post_id     INT AUTO_INCREMENT PRIMARY KEY,
    author_id   INT NOT NULL,
    body_txt    TEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    deleted_at  DATETIME NULL,
    FOREIGN KEY (author_id) REFERENCES users(user_id)
);

-- COMMENTS ---------------------------------------------
CREATE TABLE comments (
    comment_id  INT AUTO_INCREMENT PRIMARY KEY,
    post_id     INT NOT NULL,
    user_id     INT NOT NULL,
    body_txt    TEXT NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    edited_at   DATETIME NULL,
    deleted_at  DATETIME NULL,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- LIKES -----------------------------------------------
CREATE TABLE likes (
    user_id    INT NOT NULL,
    post_id    INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (post_id) REFERENCES posts(post_id)
);

-- FOLLOWS ----------------------------------------------
CREATE TABLE follows (
    user_id    INT NOT NULL,   -- follower
    profile_id INT NOT NULL,   -- followee
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, profile_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (profile_id) REFERENCES profiles(profile_id)
);

-- ACCOUNT CHANGES --------------------------------------
CREATE TABLE account_changes (
    change_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    field_changed VARCHAR(100) NOT NULL,
    old_value     TEXT,
    new_value     TEXT,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- SESSIONS ---------------------------------------------
CREATE TABLE sessions (
    session_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    login_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    logout_at    DATETIME NULL,
    login_error  VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);