<?php
/**
 * @file authSystem.php
 * @author kerogs'
 * @version 4.1
 * @date 2023-10-06
 * @update 2025-11-09
 * 
 * @description Contains auto scraping system for codes (this version work with MySQL)
 * 
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

function scrap($pdo, $prefix, $user_agent, $url)
{
    if ($url === "https://ucngame.com/codes/sword-master-story-coupon-codes/") {
        $options = [
            "http" => [
                "header" => "User-Agent: $user_agent"
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

        preg_match_all('/<strong>([A-Z0-9]+)<\/strong><\/td><td>Redeem this coupon code for ([^(]+) \(Valid until ([^)]+)\)/', $site_content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $coupon_code = $match[1];

            $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM {$prefix}codes WHERE code = :code");
            $checkStmt->bindValue(":code", $coupon_code);
            $checkStmt->execute();
            $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($result['count'] > 0) {
                continue;
            }

            $reward_description = trim($match[2]);
            $expiration_raw = preg_replace('/(\d+)(st|nd|rd|th)/', '$1', $match[3]);
            $expiration = date("Y-m-d", strtotime($expiration_raw));

            $reward_value = "";
            $reward_type = "";
            if (preg_match('/x([0-9,]+)/', $reward_description, $value_match)) {
                $reward_value = str_replace(",", "", $value_match[1]);
            }

            if (strpos($reward_description, "Ruby") !== false) {
                $reward_type = "Ruby";
            } elseif (strpos($reward_description, "Stamina") !== false) {
                $reward_type = "Stamina";
            } elseif (strpos($reward_description, "Gold Bar") !== false) {
                $reward_type = "Gold Bar";
            }

            $stmt = $pdo->prepare("INSERT INTO {$prefix}codes (code, type, value, date, description) VALUES (:code, :type, :value, :date, :description)");
            $stmt->bindValue(":code", $coupon_code);
            $stmt->bindValue(":type", $reward_type);
            $stmt->bindValue(":value", $reward_value);
            $stmt->bindValue(":date", $expiration);
            $stmt->bindValue(":description", $reward_description);
            $stmt->execute();
        }

        saveDateScrap($pdo, $prefix, $url, "ok");
        echo '<script>
            console.log("Coupons fetched successfully");
            document.addEventListener("DOMContentLoaded", function() {
                const notyf = new Notyf();
                notyf.success({
                    message: "Coupons fetched successfully",
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
