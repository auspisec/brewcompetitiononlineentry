<?php
/**
 * Admin: Privacy Consent Management
 * Manage consent text versions and view consent acceptance log.
 */
if ($_SESSION['userLevel'] > 1) {
    $redirect = $base_url . "index.php?section=list";
    header(sprintf("Location: %s", $redirect));
    exit();
}

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] == 'save_consent') {
    $language = sterilize($_POST['consent_language']);
    $consent_text = $_POST['consent_text']; // Allow HTML
    // Sanitize but allow basic tags
    $allowed_tags = '<p><br><strong><em><ul><ol><li><a><b><i>';
    $consent_text = strip_tags($consent_text, $allowed_tags);
    create_consent_version($language, $consent_text);
    $success_msg = $consent_text_019;
}

// Get data for display
$en_texts = get_all_consent_texts('en-US');
$ko_texts = get_all_consent_texts('ko-KR');
$en_active = get_active_consent_text('en-US');
$ko_active = get_active_consent_text('ko-KR');
$log_entries = get_consent_log_entries(100);
$active_tab = $_GET['tab'] ?? 'current';
?>

<div class="bcoem-admin-element">
    <div class="btn-group" role="group">
        <a class="btn btn-default" href="<?php echo $base_url; ?>index.php?section=admin"><span class="fa fa-arrow-circle-left"></span> <?php echo $label_admin_home; ?></a>
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

    <!-- English -->
    <h4>English (en-US) <?php echo $consent_text_008; ?>: <?php echo $en_active ? $en_active['version'] : 'N/A'; ?></h4>
    <div class="well">
        <?php echo $en_active ? $en_active['consent_text'] : '<em>No consent text configured.</em>'; ?>
    </div>

    <!-- Korean -->
    <h4>한국어 (ko-KR) <?php echo $consent_text_008; ?>: <?php echo $ko_active ? $ko_active['version'] : 'N/A'; ?></h4>
    <div class="well">
        <?php echo $ko_active ? $ko_active['consent_text'] : '<em>No consent text configured.</em>'; ?>
    </div>

    <hr>

    <h3><?php echo $consent_text_011; ?></h3>
    <form method="POST" action="">
        <input type="hidden" name="action" value="save_consent">
        <div class="form-group">
            <label for="consent_language"><?php echo $consent_text_009; ?></label>
            <select class="form-control" name="consent_language" id="consent_language" style="width: 200px;">
                <option value="en-US">English (en-US)</option>
                <option value="ko-KR">한국어 (ko-KR)</option>
            </select>
        </div>
        <div class="form-group">
            <label for="consent_text"><?php echo $consent_text_007; ?></label>
            <textarea class="form-control" name="consent_text" id="consent_text" rows="10" required></textarea>
            <p class="help-block">HTML allowed: &lt;p&gt;, &lt;br&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;li&gt;, &lt;a&gt;</p>
        </div>
        <button type="submit" class="btn btn-primary"><span class="fa fa-save"></span> <?php echo $consent_text_011; ?></button>
    </form>
</div>

<?php elseif ($active_tab == 'history'): ?>
<!-- Version History -->
<div style="margin-top: 20px;">
    <h3><?php echo $consent_text_022; ?></h3>

    <h4>English (en-US)</h4>
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
        <?php foreach ($en_texts as $text): ?>
            <tr>
                <td><?php echo $text['version']; ?></td>
                <td><?php echo $text['is_active'] ? '<span class="label label-success">' . $consent_text_010 . '</span>' : ''; ?></td>
                <td><?php echo $text['created_at']; ?></td>
                <td><small><?php echo substr(strip_tags($text['consent_text']), 0, 100); ?>...</small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <h4>한국어 (ko-KR)</h4>
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
        <?php foreach ($ko_texts as $text): ?>
            <tr>
                <td><?php echo $text['version']; ?></td>
                <td><?php echo $text['is_active'] ? '<span class="label label-success">' . $consent_text_010 . '</span>' : ''; ?></td>
                <td><?php echo $text['created_at']; ?></td>
                <td><small><?php echo substr(strip_tags($text['consent_text']), 0, 100); ?>...</small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
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
                <th><?php echo $consent_text_009; ?></th>
                <th><?php echo $consent_text_008; ?></th>
                <th>Consent</th>
                <th><?php echo $consent_text_016; ?></th>
                <th><?php echo $consent_text_015; ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($log_entries as $entry): ?>
            <tr>
                <td><?php echo htmlspecialchars($entry['brewerFirstName'] . ' ' . $entry['brewerLastName']); ?></td>
                <td><?php echo htmlspecialchars($entry['brewerEmail'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($entry['language']); ?></td>
                <td><?php echo htmlspecialchars($entry['version']); ?></td>
                <td><?php echo $entry['consent_given'] ? '<span class="label label-success">Yes</span>' : '<span class="label label-danger">No</span>'; ?></td>
                <td><?php echo htmlspecialchars($entry['ip_address'] ?? ''); ?></td>
                <td><?php echo $entry['created_at']; ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
