<?php
$users = array(
  "up" => "
  CREATE TABLE users (
    user_id INT,
    username VARCHAR(100),
    password_hashed VARCHAR(100),
    email VARCHAR(200),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    created TIMESTAMP DEFAULT NOW(),
    modified TIMESTAMP DEFAULT NOW()
  )",
  "down" => "
  DROP TABLE IF EXISTS users
  ");
?>