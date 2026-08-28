<?php
/**
 * Module:      bank_info.db.php
 * Description: Lazy-create and fetch the payment bank info stored in the DB.
 *
 * Seoul Cup: bank transfer details (bank name, account number, recipient)
 * are stored in the `payment_bank_info` table rather than in language
 * decode files, so no sensitive(ish) details are committed to the repo.
 * The table is created on first use if missing (self-healing), and values
 * are seeded by the Ansible bcoem-seed role from the bcoem-secrets env.
 *
 * Seoul-cup customization - not upstream.
 */

if (!function_exists("get_payment_bank_info")) {

    function bank_info_table_ready($database) {
        // Cached per-request
        static $ready = NULL;
        static $checked = FALSE;
        if ($checked) return $ready;
        $checked = TRUE;
        $ready = check_setup("payment_bank_info", $database);
        return $ready;
    }

    function bank_info_ensure_table() {
        global $db_conn, $prefix, $database;
        if (bank_info_table_ready($database)) return TRUE;

        $sql = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `bank_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `account_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `account_holder` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        $prefix."payment_bank_info");

        $db_conn->rawQuery($sql);
        return check_setup("payment_bank_info", $database);
    }

    /**
     * Returns assoc array (bank_name, account_number, account_holder) or NULL.
     */
    function get_payment_bank_info() {
        global $db_conn, $prefix, $database;

        if (!bank_info_table_ready($database)) {
            if (!bank_info_ensure_table()) return NULL;
        }

        $db_conn->orderBy("id", "ASC");
        $row = $db_conn->getOne($prefix."payment_bank_info");
        if ($db_conn->count > 0) return $row;
        return NULL;
    }

    /**
     * Builds the translated bank-transfer instruction block, overlaying the
     * DB-stored details when present. Falls back to the plain decode when
     * the table/values are not (yet) configured.
     */
    function payment_bank_transfer_text() {
        global $pay_text_015, $label_bank, $label_account_number, $label_account_holder;

        $bank = get_payment_bank_info();

        if (empty($bank) || (empty($bank['bank_name']) && empty($bank['account_number']))) {
            // Not configured yet: show the plain decode sentence.
            return $pay_text_015;
        }

        $html  = $pay_text_015;
        $html .= "<ul>";
        if (!empty($bank['bank_name']))      $html .= "<li>".htmlspecialchars($label_bank).": ".htmlspecialchars($bank['bank_name'])."</li>";
        if (!empty($bank['account_number'])) $html .= "<li>".htmlspecialchars($label_account_number).": ".htmlspecialchars($bank['account_number'])."</li>";
        if (!empty($bank['account_holder'])) $html .= "<li>".htmlspecialchars($label_account_holder).": ".htmlspecialchars($bank['account_holder'])."</li>";
        $html .= "</ul>";

        return $html;
    }

}
?>
