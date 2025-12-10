<?php
// Adjust these values before deploying.
const MYSQL_HOST = '127.0.0.1';
const MYSQL_PORT = 3306;
const MYSQL_DB = 'internship_app';
const MYSQL_USER = 'root';
const MYSQL_PASSWORD = '';

const REDIS_HOST = '127.0.0.1';
const REDIS_PORT = 6379;
const REDIS_TTL = 3600; // seconds

const MONGO_URI = 'mongodb://127.0.0.1:27017';
const MONGO_DB = 'internship_app';
const MONGO_PROFILE_COLLECTION = 'profiles';

const PDO_OPTIONS = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
];
