<?php
/**
 * Consent database helper functions.
 * Supports two consent types: 'privacy' (collection/use) and 'publication' (award publication).
 */

/**
 * Get the active consent text for a given language and consent type.
 * @param string $language Language code (e.g., 'en-US')
 * @param string $consent_type 'privacy' or 'publication'
 * @return array|null Row from consent_text table or null
 */
function get_active_consent_text($language = 'en-US', $consent_type = 'privacy') {
    global $connection, $prefix;
    $table = $prefix . "consent_text";
    $language = mysqli_real_escape_string($connection, $language);
    $consent_type = mysqli_real_escape_string($connection, $consent_type);
    $query = "SELECT * FROM $table WHERE language = '$language' AND consent_type = '$consent_type' AND is_active = 1 ORDER BY version DESC LIMIT 1";
    $result = mysqli_query($connection, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    // Fallback to en-US
    if ($language != 'en-US') {
        $query = "SELECT * FROM $table WHERE language = 'en-US' AND consent_type = '$consent_type' AND is_active = 1 ORDER BY version DESC LIMIT 1";
        $result = mysqli_query($connection, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
    }
    return null;
}

/**
 * Get the active consent text ID for a given language and type.
 */
function get_active_consent_text_id($language = 'en-US', $consent_type = 'privacy') {
    $row = get_active_consent_text($language, $consent_type);
    return $row ? $row['id'] : null;
}

/**
 * Log a consent acceptance.
 * @param int $uid User ID
 * @param int $consent_text_id Consent text version ID
 * @param int $consent_given 1=yes, 0=no
 * @param string $consent_type 'privacy' or 'publication'
 */
function log_consent($uid, $consent_text_id, $consent_given = 1, $consent_type = 'privacy', $ip = null, $user_agent = null) {
    global $connection, $prefix;
    $table = $prefix . "consent_log";
    if ($ip === null) $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($user_agent === null) $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $uid = (int)$uid;
    $consent_text_id = (int)$consent_text_id;
    $consent_given = (int)$consent_given;
    $consent_type = mysqli_real_escape_string($connection, $consent_type);
    $ip = mysqli_real_escape_string($connection, $ip);
    $user_agent = mysqli_real_escape_string($connection, $user_agent);
    $query = "INSERT INTO $table (uid, consent_text_id, consent_given, consent_type, ip_address, user_agent) VALUES ($uid, $consent_text_id, $consent_given, '$consent_type', '$ip', '$user_agent')";
    return mysqli_query($connection, $query);
}

/**
 * Check if a user needs to re-consent for a given type.
 */
function user_needs_reconsent($uid, $language = 'en-US', $consent_type = 'privacy') {
    global $connection, $prefix;
    $log_table = $prefix . "consent_log";
    $text_table = $prefix . "consent_text";
    $uid = (int)$uid;
    $language = mysqli_real_escape_string($connection, $language);
    $consent_type = mysqli_real_escape_string($connection, $consent_type);
    $active = get_active_consent_text($language, $consent_type);
    if (!$active) return false;
    $active_version = $active['version'];
    $query = "SELECT ct.version FROM $log_table cl
              JOIN $text_table ct ON cl.consent_text_id = ct.id
              WHERE cl.uid = $uid AND ct.language = '$language' AND cl.consent_type = '$consent_type'
              ORDER BY cl.created_at DESC LIMIT 1";
    $result = mysqli_query($connection, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['version'] < $active_version;
    }
    return true;
}

/**
 * Get all consent text versions for a language and type.
 */
function get_all_consent_texts($language = 'en-US', $consent_type = 'privacy') {
    global $connection, $prefix;
    $table = $prefix . "consent_text";
    $language = mysqli_real_escape_string($connection, $language);
    $consent_type = mysqli_real_escape_string($connection, $consent_type);
    $query = "SELECT * FROM $table WHERE language = '$language' AND consent_type = '$consent_type' ORDER BY version DESC";
    $result = mysqli_query($connection, $query);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    }
    return $rows;
}

/**
 * Get consent log entries with user info.
 */
function get_consent_log_entries($limit = 100, $consent_type = null) {
    global $connection, $prefix;
    $log_table = $prefix . "consent_log";
    $text_table = $prefix . "consent_text";
    $brewer_table = $prefix . "brewer";
    $limit = (int)$limit;
    $where = "";
    if ($consent_type) {
        $consent_type = mysqli_real_escape_string($connection, $consent_type);
        $where = " WHERE cl.consent_type = '$consent_type'";
    }
    $query = "SELECT cl.*, ct.language, ct.version, ct.consent_type, b.brewerFirstName, b.brewerLastName, b.brewerEmail
              FROM $log_table cl
              LEFT JOIN $text_table ct ON cl.consent_text_id = ct.id
              LEFT JOIN $brewer_table b ON cl.uid = b.uid
              $where
              ORDER BY cl.created_at DESC LIMIT $limit";
    $result = mysqli_query($connection, $query);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    }
    return $rows;
}

/**
 * Create a new consent text version (deactivates old versions for that language+type).
 */
function create_consent_version($language, $consent_text, $consent_type = 'privacy') {
    global $connection, $prefix;
    $table = $prefix . "consent_text";
    $language = mysqli_real_escape_string($connection, $language);
    $consent_type = mysqli_real_escape_string($connection, $consent_type);
    $query = "SELECT MAX(version) as max_version FROM $table WHERE language = '$language' AND consent_type = '$consent_type'";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    $next_version = ($row['max_version'] ?? 0) + 1;
    mysqli_query($connection, "UPDATE $table SET is_active = 0 WHERE language = '$language' AND consent_type = '$consent_type'");
    $consent_text_escaped = mysqli_real_escape_string($connection, $consent_text);
    $query = "INSERT INTO $table (language, version, consent_text, consent_type, is_active) VALUES ('$language', $next_version, '$consent_text_escaped', '$consent_type', 1)";
    if (mysqli_query($connection, $query)) {
        return mysqli_insert_id($connection);
    }
    return false;
}
?>
