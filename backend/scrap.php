<?php

/**
 * @file scrap.php
 * @author kerogs'
 * @version 4.3
 * @date 2023-10-06
 * @update 2025-11-11
 * 
 * @description Contains auto scraping system for codes (this version work with MySQL)
 * Improved regex and data extraction for better coupon parsing
 * Added priority system for rewards
 */

$prefix = $_ENV['DB_PREFIX'];
$host   = $_ENV['DB_HOST'];
$dbname = $_ENV['DB_NAME'];
$user   = $_ENV['DB_USER'];
$pass   = $_ENV['DB_PASS'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Vérifier si la colonne priority existe, sinon l'ajouter
$checkColumnStmt = $pdo->query("SHOW COLUMNS FROM {$prefix}codes LIKE 'priority'");
if ($checkColumnStmt->rowCount() == 0) {
    $pdo->exec("ALTER TABLE {$prefix}codes ADD COLUMN priority INT DEFAULT 3");
}

$query = "SELECT date, success FROM {$prefix}codes_log ORDER BY date DESC LIMIT 1";
$stmt = $pdo->query($query);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$should_call_code_get = false;

if (!$result) {
    $should_call_code_get = true;
} else {
    $last_date = strtotime($result['date']);
    $current_time = time();
    $time_difference = ($current_time - $last_date) / 3600;

    if ($time_difference > 2 || $result['success'] == 0) {
        $should_call_code_get = true;
    }
}

if ($should_call_code_get) {
    $rHeader = [
        "Mozilla/5.0 (iPad; CPU OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148",
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15"
    ];

    $headerSelected = $rHeader[array_rand($rHeader)];
    echo "<script>console.log('Selected User-Agent: $headerSelected');</script>";
    $r = scrap($pdo, $prefix, $headerSelected, "https://ucngame.com/codes/sword-master-story-coupon-codes/");
}

function saveDateScrap($pdo, $prefix, $url, $scrap_result, $reason = "none")
{
    $date = date("Y-m-d H:i:s");
    $stmt = $pdo->prepare("INSERT INTO {$prefix}codes_log (date, url, success) VALUES (:date, :url, :success)");
    $stmt->bindValue(":date", $date);
    $stmt->bindValue(":url", $url);
    $stmt->bindValue(":success", $scrap_result === "ok" ? 1 : 0);
    $stmt->execute();
}

function calculatePriority($reward_type, $reward_value)
{
    $priority = 4; // Priorité par défaut

    if ($reward_type === "Ruby") {
        $value = intval($reward_value);
        if ($value >= 10000) $priority = 5;
        elseif ($value >= 2000) $priority = 4;
        elseif ($value >= 1000) $priority = 3;
        elseif ($value >= 700) $priority = 2;
        else $priority = 1;
    } elseif ($reward_type === "Stamina") {
        $value = intval($reward_value);
        if ($value >= 10000) $priority = 5;
        elseif ($value >= 5000) $priority = 4;
        elseif ($value >= 4000) $priority = 3;
        elseif ($value >= 1000) $priority = 2;
        else $priority = 1;
    } elseif ($reward_type === "Summon Ticket" || $reward_type === "Character Summon Ticket") {
        $priority = 4;
    } elseif ($reward_type === "Gold Bar") {
        $priority = 3;
    } elseif ($reward_type === "Weapon Box") {
        $priority = 3;
    } elseif ($reward_type === "Symbol of Eternal Sleep" || $reward_type === "Symbol of Rare Soul") {
        $priority = 4;
    } elseif ($reward_type === "Demon Coin") {
        $priority = 2;
    } elseif ($reward_type === "Affection Potion") {
        $priority = 2;
    } elseif ($reward_type === "Refining Scroll") {
        $priority = 2;
    }

    return $priority;
}

function extractRewardInfo($reward_description)
{
    $reward_value = "";
    $reward_type = "";

    // Extract quantity with better pattern matching
    if (preg_match('/(?:x|×)(\d{1,3}(?:,\d{3})*)/', $reward_description, $value_match)) {
        $reward_value = str_replace(",", "", $value_match[1]);
    } elseif (preg_match('/(\d{1,3}(?:,\d{3})*)\s*(?:Ruby|Stamina|Gold Bar|Summon Ticket)/', $reward_description, $value_match)) {
        $reward_value = str_replace(",", "", $value_match[1]);
    }

    // Improved type detection
    $reward_lower = strtolower($reward_description);
    if (strpos($reward_lower, 'ruby summon ticket') !== false) {
        $reward_type = "Summon Ticket";
    } elseif (strpos($reward_lower, 'ruby') !== false) {
        $reward_type = "Ruby";
    } elseif (strpos($reward_lower, 'stamina') !== false) {
        $reward_type = "Stamina";
    } elseif (strpos($reward_lower, 'gold bar') !== false) {
        $reward_type = "Gold Bar";
    } elseif (strpos($reward_lower, 'summon ticket') !== false) {
        if (strpos($reward_lower, 'character summon') !== false) {
            $reward_type = "Character Summon Ticket";
        } else {
            $reward_type = "Summon Ticket"; // On garde "Summon Ticket" au lieu de "Ruby Summon Ticket"
        }
    } elseif (strpos($reward_lower, 'weapon box') !== false) {
        $reward_type = "Weapon Box";
    } elseif (strpos($reward_lower, 'symbol of eternal sleep') !== false) {
        $reward_type = "Symbol of Eternal Sleep";
    } elseif (strpos($reward_lower, 'symbol of rare soul') !== false) {
        $reward_type = "Symbol of Rare Soul";
    } elseif (strpos($reward_lower, 'demon coin') !== false) {
        $reward_type = "Demon Coin";
    } elseif (strpos($reward_lower, 'affection potion') !== false) {
        $reward_type = "Affection Potion";
    } elseif (strpos($reward_lower, 'refining scroll') !== false) {
        $reward_type = "Refining Scroll";
    } else {
        $reward_type = "Other";
    }

    return ['value' => $reward_value, 'type' => $reward_type];
}

function parseExpirationDate($expiration_raw)
{
    // Clean the date string
    $cleaned_date = preg_replace('/(\d+)(st|nd|rd|th)/', '$1', $expiration_raw);
    $cleaned_date = preg_replace('/\(New\)/', '', $cleaned_date);
    $cleaned_date = trim($cleaned_date);

    // Try to parse the date
    $timestamp = strtotime($cleaned_date);

    if ($timestamp !== false) {
        return date("Y-m-d", $timestamp);
    }

    // If no valid expiration date, set to distant future
    return "2000-01-01";
}

function scrap($pdo, $prefix, $user_agent, $url)
{
    if ($url === "https://ucngame.com/codes/sword-master-story-coupon-codes/") {
        $options = [
            "http" => [
                "header" => "User-Agent: $user_agent",
                "timeout" => 30
            ]
        ];
        $context = stream_context_create($options);
        $site_content = @file_get_contents($url, false, $context);

        if ($site_content === false) {
            echo '<script>
                console.log("Coupons Error: Failed to fetch URL");
                document.addEventListener("DOMContentLoaded", function() {
                    const notyf = new Notyf();
                    notyf.error({
                        message: "Coupons Error: Failed to fetch URL",
                        duration: 8000,
                        position: {
                            y: "bottom",
                            x: "right"
                        },
                        dismissible: true,
                        icon: "😭"
                    });
                });
            </script>';
            saveDateScrap($pdo, $prefix, $url, "ko - Failed to fetch", "Error: Failed to fetch URL");
            return 2;
        }

        // Improved regex pattern to match various coupon formats including the missing one
        $patterns = [
            // Pattern for codes with expiration dates (format corrigé pour NEXT01HERO)
            '/<tr><td><strong>([A-Z0-9]+)<\/strong><\/td><td>Redeem this coupon code for ([^<]+) \(Valid until ([^)]+)\)/',
            // Pattern for codes without expiration dates
            '/<strong>([A-Z0-9]+)<\/strong><\/td><td>Redeem this coupon code for ([^<]+)<\/td>/',
            // Alternative pattern for table structure
            '/<td><strong>([A-Z0-9]+)<\/strong><\/td>\s*<td>([^<]+)<\/td>/',
            // Pattern spécifique pour NEXT01HERO et similaires
            '/<tr><td><strong>([A-Z0-9]+)<\/strong><\/td><td>Redeem this coupon code for ([^<]+) \(Valid until ([^)]+)\)\s*<strong>\(New\)<\/strong><\/td><\/tr>/'
        ];

        $coupons_found = 0;
        $coupons_added = 0;

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $site_content, $matches, PREG_SET_ORDER);

            if (count($matches) > 0) {
                foreach ($matches as $match) {
                    $coupons_found++;
                    $coupon_code = trim($match[1]);
                    $reward_text = trim($match[2]);

                    // Extract expiration date if present
                    $expiration = "2000-01-01"; // Default far future date
                    if (isset($match[3])) {
                        $expiration = parseExpirationDate($match[3]);
                    } else {
                        // Try to extract expiration from reward text
                        if (preg_match('/\(Valid until ([^)]+)\)/', $reward_text, $exp_match)) {
                            $expiration = parseExpirationDate($exp_match[1]);
                            $reward_text = str_replace($exp_match[0], '', $reward_text);
                        }
                    }

                    // Clean reward text
                    $reward_text = preg_replace('/<strong>\(New\)<\/strong>/', '', $reward_text);
                    $reward_text = trim($reward_text);

                    // Extract reward information
                    $reward_info = extractRewardInfo($reward_text);

                    // Calculate priority based on reward type and value
                    $priority = calculatePriority($reward_info['type'], $reward_info['value']);

                    // Check if coupon already exists
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$prefix}codes WHERE code = :code");
                    $checkStmt->bindValue(":code", $coupon_code);
                    $checkStmt->execute();
                    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

                    if ($result['count'] > 0) {
                        // Update existing coupon if expiration date is newer
                        $updateStmt = $pdo->prepare("UPDATE {$prefix}codes SET date = :date, description = :description, type = :type, value = :value, priority = :priority WHERE code = :code");
                        $updateStmt->bindValue(":date", $expiration);
                        $updateStmt->bindValue(":description", $reward_text);
                        $updateStmt->bindValue(":type", $reward_info['type']);
                        $updateStmt->bindValue(":value", $reward_info['value']);
                        $updateStmt->bindValue(":priority", $priority);
                        $updateStmt->bindValue(":code", $coupon_code);
                        $updateStmt->execute();
                        continue;
                    }

                    // Insert new coupon
                    $stmt = $pdo->prepare("INSERT INTO {$prefix}codes (code, type, value, date, description, added_by, priority) VALUES (:code, :type, :value, :date, :description, :added_by, :priority)");
                    $stmt->bindValue(":code", $coupon_code);
                    $stmt->bindValue(":type", $reward_info['type']);
                    $stmt->bindValue(":value", $reward_info['value']);
                    $stmt->bindValue(":date", $expiration);
                    $stmt->bindValue(":description", $reward_text);
                    $stmt->bindValue(":added_by", "SMSDv3");
                    $stmt->bindValue(":priority", $priority);

                    if ($stmt->execute()) {
                        $coupons_added++;
                    }
                }
                // Ne pas break pour permettre de trouver tous les formats
            }
        }

        // Log the scraping results
        $log_message = "Found: $coupons_found coupons, Added: $coupons_added new coupons";
        saveDateScrap($pdo, $prefix, $url, "ok", $log_message);

        echo '<script>
            console.log("Coupons fetched successfully: ' . $coupons_found . ' found, ' . $coupons_added . ' added");
            document.addEventListener("DOMContentLoaded", function() {
                const notyf = new Notyf();
                notyf.success({
                    message: "Coupons fetched: ' . $coupons_found . ' found, ' . $coupons_added . ' new",
                    duration: 8000,
                    position: {
                        y: "bottom",
                        x: "right"
                    },
                    dismissible: true,
                    icon: "😎"
                });
            });
        </script>';
        return 1;
    } else {
        echo '<script>
            console.log("Coupons Error: URL not supported");
            document.addEventListener("DOMContentLoaded", function() {
                const notyf = new Notyf();
                notyf.error({
                    message: "Coupons Error: URL not supported",
                    duration: 8000,
                    position: {
                        y: "bottom",
                        x: "right"
                    },
                    dismissible: true,
                    icon: "😭"
                });
            });
        </script>';
        saveDateScrap($pdo, $prefix, $url, "ko - URL not supported", "Error: URL not supported");
        return 0;
    }
}
