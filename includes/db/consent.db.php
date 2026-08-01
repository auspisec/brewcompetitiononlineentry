<?php
/**
 * Consent database helper functions.
 * Provides privacy consent text management and audit logging.
 */

/**
 * Get the active consent text for a given language (falls back to en-US).
 */
function get_active_consent_text($language = 'en-US') {
    global $connection, $prefix;
    $table = $prefix . "consent_text";
    $language = mysqli_real_escape_string($connection, $language);
    $query = "SELECT * FROM $table WHERE language = '$language' AND is_active = 1 ORDER BY version DESC LIMIT 1";
    $result = mysqli_query($connection, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    // Fallback to en-US
    if ($language != 'en-US') {
        $query = "SELECT * FROM $table WHERE language = 'en-US' AND is_active = 1 ORDER BY version DESC LIMIT 1";
        $result = mysqli_query($connection, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
    }
    return null;
}

/**
 * Get the active consent text ID for a given language.
 */
function get_active_consent_text_id($language = 'en-US') {
    $row = get_active_consent_text($language);
    return $row ? $row['id'] : null;
}

/**
 * Log a consent acceptance.
 */
function log_consent($uid, $consent_text_id, $consent_given = 1, $ip = null, $user_agent = null) {
    global $connection, $prefix;
    $table = $prefix . "consent_log";
    if ($ip === null) $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($user_agent === null) $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    $uid = (int)$uid;
    $consent_text_id = (int)$consent_text_id;
    $consent_given = (int)$consent_given;
    $ip = mysqli_real_escape_string($connection, $ip);
    $user_agent = mysqli_real_escape_string($connection, $user_agent);
    $query = "INSERT INTO $table (uid, consent_text_id, consent_given, ip_address, user_agent) VALUES ($uid, $consent_text_id, $consent_given, '$ip', '$user_agent')";
    return mysqli_query($connection, $query);
}

/**
 * Check if a user needs to re-consent (their latest consent version != active version).
 */
function user_needs_reconsent($uid, $language = 'en-US') {
    global $connection, $prefix;
    $log_table = $prefix . "consent_log";
    $text_table = $prefix . "consent_text";
    $uid = (int)$uid;
    $language = mysqli_real_escape_string($connection, $language);
    // Get the active consent text ID
    $active = get_active_consent_text($language);
    if (!$active) return false; // No consent text configured — don't block
    $active_version = $active['version'];
    // Get user's latest consent log entry for this language
    $query = "SELECT ct.version FROM $log_table cl
              JOIN $text_table ct ON cl.consent_text_id = ct.id
              WHERE cl.uid = $uid AND ct.language = '$language'
              ORDER BY cl.created_at DESC LIMIT 1";
    $result = mysqli_query($connection, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['version'] < $active_version;
    }
    // No consent log at all — needs to consent
    return true;
}

/**
 * Get all consent text versions for a language (for admin display).
 */
function get_all_consent_texts($language = 'en-US') {
    global $connection, $prefix;
    $table = $prefix . "consent_text";
    $language = mysqli_real_escape_string($connection, $language);
    $query = "SELECT * FROM $table WHERE language = '$language' ORDER BY version DESC";
    $result = mysqli_query($connection, $query);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    }
    return $rows;
}

/**
 * Get consent log entries with user info (for admin display).
 */
function get_consent_log_entries($limit = 100) {
    global $connection, $prefix;
    $log_table = $prefix . "consent_log";
    $text_table = $prefix . "consent_text";
    $brewer_table = $prefix . "brewer";
    $limit = (int)$limit;
    $query = "SELECT cl.*, ct.language, ct.version, b.brewerFirstName, b.brewerLastName, b.brewerEmail
              FROM $log_table cl
              LEFT JOIN $text_table ct ON cl.consent_text_id = ct.id
              LEFT JOIN $brewer_table b ON cl.uid = b.uid
              ORDER BY cl.created_at DESC LIMIT $limit";
    $result = mysqli_query($connection, $query);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    }
    return $rows;
}

/**
 * Create a new consent text version (deactivates old versions for that language).
 */
function create_consent_version($language, $consent_text) {
    global $connection, $prefix;
    $table = $prefix . "consent_text";
    $language = mysqli_real_escape_string($connection, $language);
    // Get next version number
    $query = "SELECT MAX(version) as max_version FROM $table WHERE language = '$language'";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    $next_version = ($row['max_version'] ?? 0) + 1;
    // Deactivate old versions
    mysqli_query($connection, "UPDATE $table SET is_active = 0 WHERE language = '$language'");
    // Insert new version
    $consent_text_escaped = mysqli_real_escape_string($connection, $consent_text);
    $query = "INSERT INTO $table (language, version, consent_text, is_active) VALUES ('$language', $next_version, '$consent_text_escaped', 1)";
    if (mysqli_query($connection, $query)) {
        return mysqli_insert_id($connection);
    }
    return false;
}
?>
