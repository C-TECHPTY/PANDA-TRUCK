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
