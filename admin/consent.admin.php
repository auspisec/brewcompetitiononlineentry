<?php
/**
 * Admin: Privacy Consent Management
 * Manage consent text versions and view consent acceptance log.
 * Language-generic: iterates over $languages from constants.inc.php.
 * Supports two consent types: 'privacy' and 'publication'.
 */
include_once(DB.'consent.db.php');

if ($_SESSION['userLevel'] > 1) {
    $redirect = $base_url . "index.php?section=list";
    header(sprintf("Location: %s", $redirect));
    exit();
}

// Handle inline form submission
if (isset($_POST['consent_action']) && $_POST['consent_action'] == 'save_consent') {
    $language = sterilize($_POST['consent_language']);
    $consent_type = sterilize($_POST['consent_type'] ?? 'privacy');
    $consent_text = $_POST['consent_text'];
    $allowed_tags = '<p><br><strong><em><ul><ol><li><a><b><i>';
    $consent_text = strip_tags($consent_text, $allowed_tags);
    create_consent_version($language, $consent_text, $consent_type);
    $success_msg = $consent_text_019;
}

// Get the languages array
$consent_languages = $GLOBALS['languages'] ?? array('en-US' => 'English (US)');

// Consent types
$consent_types = array(
    'privacy' => $consent_text_001,
    'publication' => $consent_text_026,
);

// Get data for display
$all_texts = array();
$active_texts = array();
foreach ($consent_languages as $lang_code => $lang_name) {
    foreach ($consent_types as $type_code => $type_name) {
        $key = $lang_code . '_' . $type_code;
        $all_texts[$key] = get_all_consent_texts($lang_code, $type_code);
        $active_texts[$key] = get_active_consent_text($lang_code, $type_code);
    }
}
$log_entries = get_consent_log_entries(100);
$active_tab = $_GET['tab'] ?? 'current';
$default_lang = $_SESSION['prefsLanguage'] ?? 'en-US';
$edit_lang = $_GET['lang'] ?? $default_lang;
$edit_type = $_GET['type'] ?? 'privacy';
$edit_active = get_active_consent_text($edit_lang, $edit_type);
?>

<div class="bcoem-admin-element">
    <div class="btn-group" role="group">
        <a class="btn btn-default" href="<?php echo $base_url; ?>index.php?section=admin"><span class="fa fa-arrow-circle-left"></span> Admin</a>
    </div>
</div>

<h2><?php echo $consent_text_006; ?></h2>

<?php if (isset($success_msg)): ?>
<div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs">
    <li class="<?php echo $active_tab == 'current' ? 'active' : ''; ?>"><a href="<?php echo $base_url; ?>index.php?section=admin&go=consent&tab=current"><?php echo $consent_text_007; ?></a></li>
    <li class="<?php echo $active_tab == 'history' ? 'active' : ''; ?>"><a href="<?php echo $base_url; ?>index.php?section=admin&go=consent&tab=history"><?php echo $consent_text_022; ?></a></li>
    <li class="<?php echo $active_tab == 'log' ? 'active' : ''; ?>"><a href="<?php echo $base_url; ?>index.php?section=admin&go=consent&tab=log"><?php echo $consent_text_013; ?></a></li>
</ul>

<?php if ($active_tab == 'current'): ?>
<!-- Current / Edit -->
<div style="margin-top: 20px;">
    <h3><?php echo $consent_text_007; ?></h3>

    <?php foreach ($consent_types as $type_code => $type_name): ?>
    <h4><?php echo htmlspecialchars($type_name); ?></h4>
    <?php foreach ($consent_languages as $lang_code => $lang_name): ?>
        <?php $key = $lang_code . '_' . $type_code; ?>
        <h5><?php echo htmlspecialchars($lang_name); ?> (<?php echo htmlspecialchars($lang_code); ?>) <?php echo $consent_text_008; ?>: <?php echo $active_texts[$key] ? $active_texts[$key]['version'] : 'N/A'; ?></h5>
        <div class="well">
            <?php echo $active_texts[$key] ? $active_texts[$key]['consent_text'] : '<em>No consent text configured.</em>'; ?>
        </div>
        <p><a href="<?php echo $base_url; ?>index.php?section=admin&go=consent&tab=current&lang=<?php echo urlencode($lang_code); ?>&type=<?php echo urlencode($type_code); ?>" class="btn btn-xs btn-default"><span class="fa fa-edit"></span> Edit this text</a></p>
    <?php endforeach; ?>
    <?php endforeach; ?>

    <hr>

    <h3><?php echo $consent_text_011; ?> — <?php echo htmlspecialchars($consent_types[$edit_type] ?? $consent_text_001); ?></h3>
    <form method="POST" action="<?php echo $base_url; ?>includes/process.inc.php?action=save_consent&amp;section=admin&amp;go=consent">
        <input type="hidden" name="consent_action" value="save_consent">
        <input type="hidden" name="user_session_token" value="<?php if (isset($_SESSION['user_session_token'])) echo htmlspecialchars($_SESSION['user_session_token'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="form-group">
            <label for="consent_type"><?php echo $consent_text_027; ?></label>
            <select class="form-control" name="consent_type" id="consent_type" style="width: 300px;">
                <?php foreach ($consent_types as $type_code => $type_name): ?>
                <option value="<?php echo htmlspecialchars($type_code); ?>" <?php if ($type_code == $edit_type) echo 'selected'; ?>><?php echo htmlspecialchars($type_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="consent_language"><?php echo $consent_text_009; ?></label>
            <select class="form-control" name="consent_language" id="consent_language" style="width: 300px;">
                <?php foreach ($consent_languages as $lang_code => $lang_name): ?>
                <option value="<?php echo htmlspecialchars($lang_code); ?>" <?php if ($lang_code == $edit_lang) echo 'selected'; ?>><?php echo htmlspecialchars($lang_name); ?> (<?php echo htmlspecialchars($lang_code); ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="consent_text"><?php echo $consent_text_007; ?></label>
            <textarea class="form-control" name="consent_text" id="consent_text" rows="10" required><?php echo $edit_active ? htmlspecialchars($edit_active['consent_text']) : ''; ?></textarea>
            <p class="help-block">HTML allowed: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a&gt;</p>
            <p class="help-block"><em>The text above is pre-filled with the current active version. Edit and save to create a new version.</em></p>
        </div>
        <button type="submit" class="btn btn-primary"><span class="fa fa-save"></span> <?php echo $consent_text_011; ?></button>
    </form>
</div>

<?php elseif ($active_tab == 'history'): ?>
<!-- Version History -->
<div style="margin-top: 20px;">
    <h3><?php echo $consent_text_022; ?></h3>

    <?php foreach ($consent_types as $type_code => $type_name): ?>
    <h4><?php echo htmlspecialchars($type_name); ?></h4>
    <?php foreach ($consent_languages as $lang_code => $lang_name): ?>
        <?php $key = $lang_code . '_' . $type_code; ?>
    <h5><?php echo htmlspecialchars($lang_name); ?> (<?php echo htmlspecialchars($lang_code); ?>)</h5>
    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th><?php echo $consent_text_008; ?></th>
                <th><?php echo $consent_text_024; ?></th>
                <th><?php echo $consent_text_023; ?></th>
                <th><?php echo $consent_text_007; ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($all_texts[$key])): ?>
        <?php foreach ($all_texts[$key] as $text): ?>
            <tr>
                <td><?php echo $text['version']; ?></td>
                <td><?php echo $text['is_active'] ? '<span class="label label-success">' . $consent_text_010 . '</span>' : ''; ?></td>
                <td><?php echo $text['created_at']; ?></td>
                <td><small><?php echo substr(strip_tags($text['consent_text']), 0, 100); ?>...</small></td>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4"><em>No versions.</em></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php endforeach; ?>
    <?php endforeach; ?>
</div>

<?php elseif ($active_tab == 'log'): ?>
<!-- Consent Log -->
<div style="margin-top: 20px;">
    <h3><?php echo $consent_text_013; ?></h3>
    <table class="table table-striped table-bordered" id="consent-log-table">
        <thead>
            <tr>
                <th><?php echo $consent_text_014; ?></th>
                <th>Email</th>
                <th><?php echo $consent_text_027; ?></th>
                <th><?php echo $consent_text_009; ?></th>
                <th><?php echo $consent_text_008; ?></th>
                <th>Consent</th>
                <th><?php echo $consent_text_016; ?></th>
                <th><?php echo $consent_text_015; ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($log_entries)): ?>
        <?php foreach ($log_entries as $entry): ?>
            <tr>
                <td><?php echo htmlspecialchars($entry['brewerFirstName'] . ' ' . $entry['brewerLastName']); ?></td>
                <td><?php echo htmlspecialchars($entry['brewerEmail'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($entry['consent_type'] ?? 'privacy'); ?></td>
                <td><?php echo htmlspecialchars($entry['language']); ?></td>
                <td><?php echo htmlspecialchars($entry['version']); ?></td>
                <td><?php echo $entry['consent_given'] ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>'; ?></td>
                <td><?php echo htmlspecialchars($entry['ip_address'] ?? ''); ?></td>
                <td><?php echo $entry['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8"><em>No consent log entries.</em></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
