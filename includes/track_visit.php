<?php
// includes/track_visit.php - Registro interno de visitas publicas.

if (!function_exists('detectDeviceType')) {
    function detectDeviceType($userAgent) {
        $ua = strtolower($userAgent ?? '');
        if (strpos($ua, 'tablet') !== false || strpos($ua, 'ipad') !== false) {
            return 'tablet';
        }
        if (strpos($ua, 'mobile') !== false || strpos($ua, 'android') !== false || strpos($ua, 'iphone') !== false) {
            return 'mobile';
        }
        return 'desktop';
    }
}

if (!function_exists('shouldSkipVisitTracking')) {
    function shouldSkipVisitTracking($uri) {
        $path = strtolower(parse_url($uri, PHP_URL_PATH) ?? '');
        $blocked = ['/dashboard', '/login', '/admin', '/api', '/cron', '/uploads', '/assets'];

        foreach ($blocked as $prefix) {
            if (strpos($path, $prefix) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('ensureSiteVisitsTable')) {
    function ensureSiteVisitsTable($db = null) {
        static $checked = false;

        if ($checked) {
            return true;
        }

        try {
            $db = $db ?: getDB();
            $db->exec("CREATE TABLE IF NOT EXISTS `site_visits` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `page_type` VARCHAR(50) NOT NULL,
                `page_url` TEXT NOT NULL,
                `related_id` INT NULL,
                `dj_id` INT NULL,
                `mix_id` INT NULL,
                `ip_hash` CHAR(64) NOT NULL,
                `user_agent` TEXT NULL,
                `device_type` VARCHAR(30) NULL,
                `referer` TEXT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_site_visits_page_type` (`page_type`),
                KEY `idx_site_visits_created_at` (`created_at`),
                KEY `idx_site_visits_dj_id` (`dj_id`),
                KEY `idx_site_visits_mix_id` (`mix_id`),
                KEY `idx_site_visits_related_id` (`related_id`),
                KEY `idx_site_visits_ip_created` (`ip_hash`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $checked = true;
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('trackVisit')) {
    function trackVisit($pageType, $relatedId = null, $djId = null, $mixId = null) {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (shouldSkipVisitTracking($uri)) {
            return false;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        $ipHash = hash('sha256', $ip . '|' . $userAgent . '|' . DB_NAME);
        $pageUrl = $uri ?: ($_SERVER['SCRIPT_NAME'] ?? '');
        $dedupeKey = 'visit_' . sha1($pageType . '|' . $pageUrl . '|' . $relatedId . '|' . $djId . '|' . $mixId);
        $now = time();

        if (isset($_SESSION[$dedupeKey]) && ($now - $_SESSION[$dedupeKey]) < 60) {
            return false;
        }

        try {
            $db = getDB();
            if (!ensureSiteVisitsTable($db)) {
                return false;
            }

            $stmt = $db->prepare("SELECT id FROM site_visits
                                  WHERE page_type = :page_type
                                    AND page_url = :page_url
                                    AND ip_hash = :ip_hash
                                    AND created_at >= DATE_SUB(NOW(), INTERVAL 60 SECOND)
                                  LIMIT 1");
            $stmt->execute([
                ':page_type' => $pageType,
                ':page_url' => $pageUrl,
                ':ip_hash' => $ipHash,
            ]);

            if ($stmt->fetch()) {
                $_SESSION[$dedupeKey] = $now;
                return false;
            }

            $stmt = $db->prepare("INSERT INTO site_visits
                (page_type, page_url, related_id, dj_id, mix_id, ip_hash, user_agent, device_type, referer)
                VALUES
                (:page_type, :page_url, :related_id, :dj_id, :mix_id, :ip_hash, :user_agent, :device_type, :referer)");
            $stmt->execute([
                ':page_type' => $pageType,
                ':page_url' => $pageUrl,
                ':related_id' => $relatedId,
                ':dj_id' => $djId,
                ':mix_id' => $mixId,
                ':ip_hash' => $ipHash,
                ':user_agent' => $userAgent,
                ':device_type' => detectDeviceType($userAgent),
                ':referer' => $referer,
            ]);

            $_SESSION[$dedupeKey] = $now;
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
?>
