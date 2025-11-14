<?php
session_start();

const smsdv3_version = "3.4.1-beta";

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/authSystem.php';
require_once __DIR__ . '/func.php';
require_once __DIR__ . '/class.php';
require_once __DIR__ . '/../backend/scrap.php';