<?php
/**
 * Module:      bos_combine.db.php
 * Description: Generic combined Best of Show support.
 *
 * Seoul Cup: allows any style type to declare, via the `styleTypeIncludes`
 * column on `style_types` (comma-separated style type ids), that its BOS
 * round should also pull candidates from the listed types. Replaces the
 * hardcoded "Mead/Cider" (type 4) special case while keeping it working.
 *
 * - styleTypeBOS='Y' + empty styleTypeIncludes = normal single-type BOS
 * - styleTypeIncludes='2,3,4' = BOS pulls scoreType IN (own id, 2, 3, 4)
 *
 * The column is added lazily (self-healing) if missing, so no manual
 * migration is required on dev/prod. Seoul-cup customization - not upstream.
 */

if (!function_exists("bos_combine_ensure_column")) {

    function bos_combine_ensure_column() {
        global $db_conn, $prefix;
        static $checked = FALSE;
        static $ready = FALSE;
        if ($checked) return $ready;
        $checked = TRUE;

        $ready = check_update("styleTypeIncludes", $prefix."style_types");
        if (!$ready) {
            $sql = sprintf("ALTER TABLE `%s` ADD `styleTypeIncludes` VARCHAR(100) NULL DEFAULT NULL AFTER `styleTypeBOSMethod`;", $prefix."style_types");
            $db_conn->rawQuery($sql);
            $ready = check_update("styleTypeIncludes", $prefix."style_types");
        }
        return $ready;
    }

    /**
     * Returns the raw styleTypeIncludes value for a style type id, or ''.
     */
    function bos_combine_get_includes($type_id) {
        global $db_conn, $prefix;
        if (!bos_combine_ensure_column()) return "";
        $row = $db_conn->where("id", $type_id)->getOne($prefix."style_types", "styleTypeIncludes");
        if (($db_conn->count > 0) && (!empty($row['styleTypeIncludes']))) return $row['styleTypeIncludes'];
        return "";
    }

    /**
     * Returns true if the given style type combines others into its BOS.
     */
    function bos_combine_is_combined($type_id) {
        return (bos_combine_get_includes($type_id) !== "");
    }

    /**
     * Builds a `scoreType IN (...)` SQL fragment covering the type itself
     * plus every type listed in its styleTypeIncludes. Always safe: the
     * ids are cast to int.
     */
    function bos_combine_scoretype_sql($type_id) {
        $ids = array(intval($type_id));
        $includes = bos_combine_get_includes($type_id);
        if ($includes !== "") {
            foreach (explode(",", $includes) as $inc) {
                $inc = intval(trim($inc));
                if (($inc > 0) && (!in_array($inc, $ids))) $ids[] = $inc;
            }
        }
        $parts = array();
        foreach ($ids as $id) $parts[] = "scoreType='".intval($id)."'";
        return "(".implode(" OR ", $parts).")";
    }

}
?>
