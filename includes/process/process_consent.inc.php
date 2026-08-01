<?php
/**
 * Process: Save privacy consent text (new version)
 */
include_once(DB.'consent.db.php');

if ($_SESSION['userLevel'] <= 1) {
    $language = sterilize($_POST['consent_language']);
    $consent_text = $_POST['consent_text'];
    $allowed_tags = '<p><br><strong><em><ul><ol><li><a><b><i>';
    $consent_text = strip_tags($consent_text, $allowed_tags);
    create_consent_version($language, $consent_text);
}

// Redirect back to consent admin page
$redirect = $base_url . "index.php?section=admin&go=consent&tab=current";
$redirect = prep_redirect_link($redirect);
$redirect_go_to = sprintf("Location: %s", $redirect);
header($redirect_go_to);
exit();
?>
