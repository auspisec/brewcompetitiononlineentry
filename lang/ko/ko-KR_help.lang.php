<?php
$list_help_title = "내 계정 도움말";
$list_help_body = "<p>이 화면은 계정 정보의 종합적인 스냅샷입니다.</p>";
$list_help_body .= "<p>Here, you can view your personal information including name, address, phone number(s), clubs, AHA member number, BJCP ID, BJCP judge rank, judging preferences, and stewarding preferences.</p>";
$list_help_body .= "<ul>";
$list_help_body .= "<li>Select the &ldquo;Edit Account&rdquo; button to update your personal information.</li>";
$list_help_body .= "<li>Select the &ldquo;Change Email&rdquo; button to update your email address. <strong>Note:</strong> your email address is also your user name.</li>";
$list_help_body .= "<li>Select the &ldquo;Change Password&rdquo; button to update your account password.</li>";
$list_help_body .= "</ul>";
$list_help_body .= "<p>At the bottom of the page is your list of entries.</p>";
$list_help_body .= "<ul>";
$list_help_body .= "<li>Select the printer icon <span class=\"fa fa-print\"></span> to print the necessary documentation for each entry (bottle labels, etc.).</li>";
$list_help_body .= "<li>Select the pencil icon <span class=\"fa fa-pencil\"></span> to edit the entry.</li>";
$list_help_body .= "<li>Select the trash can icon <span class=\"fa fa-trash-o\"></span> to delete the entry.</li>";
$list_help_body .= "</ul>";

$brewer_acct_edit_help_title = "계정 편집 도움말";
$brewer_acct_edit_help_body = "<p>여기서 주소/전화번호, AHA 회원 번호, BJCP ID, BJCP 심사 등급, 심사 또는 스튜어드 가능 지역과 선호도 등을 포함한 계정 정보를 업데이트할 수 있습니다.</p>";

$username_help_title = "이메일 주소 변경 도움말";
$username_help_body = "<p>여기서 이메일 주소를 변경할 수 있습니다.</p>";
$username_help_body .= "<p><strong>Please Note:</strong> your email address also serves as your user name to access your account on this site.</p>";

$password_help_title = "비밀번호 변경 도움말";
$password_help_body = "<p>여기서 사이트 접속 비밀번호를 변경할 수 있습니다. 더 안전할수록 좋습니다 – 특수 문자 및 숫자를 포함하세요 (Here, you can change your access password to this site. The more secure, the better – include special characters and/or numbers).</p>";

$pay_help_title = "출품비 결제 도움말";
$pay_help_body = "<p>이 화면에는 미납 출품과 관련 수수료가 표시됩니다. 대회 주최 측에서 참가자에게 코드가 있는 할인 혜택을 제공하는 경우, 출품 결제 전에 코드를 입력할 수 있습니다.</p>";
$pay_help_body .= "<p>For the ".$_SESSION['contestName'].", accepted payment methods are:</p>";
$pay_help_body .= "<ul>";
if ($_SESSION['prefsCash'] == "Y") $pay_help_body .= "<li><strong>Cash.</strong> Put cash in an envelope and attach to one of your bottles. Please, for the sanity of the organizing staff, do not pay with coins.</li>";
if ($_SESSION['prefsCheck'] == "Y") $pay_help_body .= "<li><strong>Check.</strong> Make your check out to ".$_SESSION['prefsCheckPayee']." for the full amount of your entry fees, place in an envelope, and attach to one of your bottles. It would be extremely helpful for competition staff if you would list your entry numbers in the memo section.</li>";
if ($_SESSION['prefsPaypal'] == "Y") $pay_help_body .= "<li><strong>Credit/Debit Card via PayPal.</strong> To pay your entry fees with a credit or debit card, select the &ldquo;Pay with PayPal&rdquo; button. A PayPal account is not necessary. After you have paid, be sure to click the &ldquo;Return to...&rdquo; link on the PayPal confirmation screen. This will ensure that your entries are marked as paid for this competition.</li>";
$pay_help_body .= "</ul>";
?>
