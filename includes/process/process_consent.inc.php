<?php
/**
 * Process: Save privacy consent text (new version)
 */
include_once(DB.'consent.db.php');

if ($_SESSION['userLevel'] <= 1) {
    $language = sterilize($_POST['consent_language']);
    $consent_text = $_POST['consent_text'];
    // Use HTML Purifier instead of strip_tags() — strip_tags() preserves
    // event handler attributes (onclick, onmouseover) and javascript: URLs
    // in href attributes, enabling stored XSS.
    require(CLASSES.'htmlpurifier/HTMLPurifier.standalone.php');
    $config = HTMLPurifier_Config::createDefault();
    // Allow only safe inline formatting tags
    $config->set('HTML.Allowed', 'p,br,strong,em,ul,ol,li,b,i,a[href]');
    $config->set('URI.AllowedSchemes', array('http' => true, 'https' => true, 'mailto' => true));
    $config->set('AutoFormat.AutoParagraph', true);
    $purifier = new HTMLPurifier($config);
    $consent_text = $purifier->purify($consent_text);
    create_consent_version($language, $consent_text);
}

// Redirect back to consent admin page
$redirect = $base_url . "index.php?section=admin&go=consent&tab=current";
$redirect = prep_redirect_link($redirect);
$redirect_go_to = sprintf("Location: %s", $redirect);
header($redirect_go_to);
exit();
?>
