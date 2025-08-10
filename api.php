<?php
// api.php – v1.5 (no borrower_id in verification codes)
header("Content-Type: application/json; charset=UTF-8");
include "config/config.php";  // defines $conn, INFOBIP_API_KEY

function respond(int $code, array $payload=[]) {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function getInput(): array {
  $d = json_decode(file_get_contents('php://input'), true);
  if (json_last_error()!==JSON_ERROR_NONE) {
    respond(400,['status'=>'error','message'=>'Invalid JSON']);
  }
  return $d;
}

function authenticateBorrower(): array {
    global $conn;
    $hdr = getallheaders();
    $auth = '';
    if (!empty($hdr['Authorization'])) {
        $auth = $hdr['Authorization'];
    } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (empty($auth) || !preg_match('/Bearer\s+(.+)$/i', $auth, $m)) {
        respond(401, ['status'=>'error','message'=>'Missing auth token']);
    }
    $token = $conn->real_escape_string($m[1]);
    $stmt = $conn->prepare("
        SELECT borrower_id FROM tbl_borrower_tokens
        WHERE token=? AND (expires_at IS NULL OR expires_at>NOW())
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $r = $stmt->get_result();
    if (!$r || $r->num_rows!==1) respond(401,['status'=>'error','message'=>'Invalid token']);
    $borrower_id = (int)$r->fetch_assoc()['borrower_id'];
    return ['borrower_id' => $borrower_id, 'token' => $m[1]];
}

function authenticateAdmin(): array {
  global $conn;
  $hdr = getallheaders();
  if (empty($hdr['Authorization'])
    || !preg_match('/Bearer\s+(.+)$/i',$hdr['Authorization'],$m)
  ) respond(401,['status'=>'error','message'=>'Missing auth token']);
  $token = $conn->real_escape_string($m[1]);
  $stmt = $conn->prepare("
    SELECT user_id,role_id FROM tbl_api_tokens
     WHERE token=? AND expires_at>NOW() LIMIT 1
  ");
  $stmt->bind_param("s",$token);
  $stmt->execute();
  $r = $stmt->get_result();
  if (!$r || $r->num_rows!==1) respond(401,['status'=>'error','message'=>'Invalid token']);
  return $r->fetch_assoc();
}

$input  = getInput();
$action = $input['action'] ?? '';

// ── PUBLIC ──────────────────────────────────────────────────
switch ($action) {
case 'check_borrower_exists':
    $email = $conn->real_escape_string($input['email'] ?? '');
    $nid = $conn->real_escape_string($input['nid'] ?? '');
    $mobile = $conn->real_escape_string($input['mobile'] ?? '');
    $stmt = $conn->prepare("
      SELECT id FROM tbl_borrower 
      WHERE email=? OR nid=? OR mobile=? LIMIT 1
    ");
    $stmt->bind_param("sss", $email, $nid, $mobile);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
      respond(200, ['exists' => true, 'message' => 'A user with this email, NID, or phone already exists.']);
    } else {
      respond(200, ['exists' => false]);
    }
                
  // 1) Send OTP via Infobip
case 'store_verification_code':
    $method = $input['method'] ?? '';
    $target = preg_replace('/\D/','',$input['target'] ?? '');
    $code   = $conn->real_escape_string($input['code'] ?? '');
    if ($method!=='phone' || strlen($code)<4) {
      respond(422,['status'=>'error','message'=>'Invalid request']);
    }
    $expires = date('Y-m-d H:i:s',strtotime('+5 minutes'));
    $stmt = $conn->prepare("
      INSERT INTO tbl_verification_codes
        (method,target,code,expires_at)
      VALUES('phone',?,?,?)
    ");
    $stmt->bind_param("sss",$target,$code,$expires);
    $stmt->execute();
    respond(200, ['status'=>'success','message'=>'Code stored']);
  // 2) Verify OTP
  case 'verify_code':
    $method = $input['method'] ?? '';
    $code   = $conn->real_escape_string($input['code'] ?? '');
    $target = preg_replace('/\D/','',$input['target'] ?? '');
    if ($method!=='phone' || strlen($code)<4) {
      respond(422,['status'=>'error','message'=>'Invalid request']);
    }
    $stmt = $conn->prepare("
      SELECT id FROM tbl_verification_codes
       WHERE method='phone' AND target=? AND code=?
         AND used_at IS NULL AND expires_at>NOW()
       LIMIT 1
    ");
    $stmt->bind_param("ss",$target,$code);
    $stmt->execute();
    $r = $stmt->get_result();
    if (!$r || $r->num_rows===0) {
      respond(400,['status'=>'error','message'=>'Wrong or expired code']);
    }
    $vcId = $r->fetch_assoc()['id'];
    $stmt = $conn->prepare("
      UPDATE tbl_verification_codes
         SET used_at=NOW()
       WHERE id=?
    ");
    $stmt->bind_param("i",$vcId);
    $stmt->execute();
    respond(200,['status'=>'success','message'=>'Code verified']);

  // 3) Register borrower
  case 'register_borrower':
    foreach (['name','email','password','nid','mobile','gender','dob','address','working_status'] as $f) {
      if (empty($input[$f])) {
        respond(422,['status'=>'error','message'=>"Missing $f"]);
      }
    }
    $n  = $conn->real_escape_string($input['name']);
    $e  = $conn->real_escape_string($input['email']);
    $h  = password_hash($input['password'],PASSWORD_BCRYPT);
    $nid= $conn->real_escape_string($input['nid']);
    $mob= $conn->real_escape_string($input['mobile']);
    $g  = $conn->real_escape_string($input['gender']);
    $d  = $conn->real_escape_string($input['dob']);
    $a  = $conn->real_escape_string($input['address']);
    $w  = $conn->real_escape_string($input['working_status']);
    // check duplicates
    $stmt = $conn->prepare("
      SELECT id FROM tbl_borrower 
       WHERE email=? OR nid=? OR mobile=? LIMIT 1
    ");
    $stmt->bind_param("sss",$e,$nid,$mob);
    $stmt->execute();
    if ($stmt->get_result()->num_rows) {
      respond(409,['status'=>'error','message'=>'Already exists']);
    }
    // insert
    $stmt = $conn->prepare("
      INSERT INTO tbl_borrower
        (name,email,password_hash,nid,mobile,gender,dob,address,working_status)
      VALUES(?,?,?,?,?,?,?,?,?)
    ");
    $stmt->bind_param("sssssssss",$n,$e,$h,$nid,$mob,$g,$d,$a,$w);
    $stmt->execute();
    respond(201,['status'=>'success','borrower_id'=>$conn->insert_id]);

  // 4) Borrower login
  case 'login_borrower':
    if (empty($input['email'])||empty($input['password'])) {
      respond(422,['status'=>'error','message'=>'Email & password required']);
    }
    $e = $conn->real_escape_string($input['email']);
    $stmt = $conn->prepare("
      SELECT id,password_hash FROM tbl_borrower WHERE email=? LIMIT 1
    ");
    $stmt->bind_param("s",$e);
    $stmt->execute();
    $r = $stmt->get_result();
    if (!$r||$r->num_rows===0) {
      respond(401,['status'=>'error','message'=>'Invalid credentials']);
    }
    $u = $r->fetch_assoc();
    if (!password_verify($input['password'],$u['password_hash'])) {
      respond(401,['status'=>'error','message'=>'Invalid credentials']);
    }
    $token = bin2hex(random_bytes(24));
    $stmt = $conn->prepare("
      INSERT INTO tbl_borrower_tokens(token,borrower_id,expires_at)
      VALUES(?,?,NULL)
    ");
    $stmt->bind_param("si",$token,$u['id']);
    $stmt->execute();
    respond(200,['status'=>'success','token'=>$token]);

  // 5) Public interest rates
  case 'list_interest_rates':
    $out=[];
    $stmt = $conn->prepare("
      SELECT id,annual_rate_percent,effective_date
        FROM tbl_interest_rate ORDER BY effective_date DESC
    ");
    $stmt->execute();
    $qr = $stmt->get_result();
    while($r=$qr->fetch_assoc()) $out[]=$r;
    respond(200,['status'=>'success','rates'=>$out]);

  // 6) Fetch Terms & Conditions
  case 'get_terms_conditions':
    $stmt = $conn->prepare("SELECT content FROM tbl_terms_conditions ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) {
      respond(200, ['status' => 'success', 'content' => $row['content']]);
    } else {
      respond(404, ['status' => 'error', 'message' => 'No terms and conditions found']);
    }

  // 7) List all branches
  case 'list_branches':
    $branches = [];
    $qr = $conn->query("SELECT id, name, address, manager_user_id, created_at, updated_at FROM tbl_branch ORDER BY name ASC");
    while ($row = $qr->fetch_assoc()) $branches[] = $row;
    respond(200, ['status' => 'success', 'branches' => $branches]);

  // 8) List all roles
  case 'list_roles':
    $roles = [];
    $qr = $conn->query("SELECT id, name, description FROM tbl_role ORDER BY id ASC");
    while ($row = $qr->fetch_assoc()) $roles[] = $row;
    respond(200, ['status' => 'success', 'roles' => $roles]);

  // 9) List all users (officers/managers)
  case 'list_users':
    $users = [];
    $qr = $conn->query("SELECT id, name, email, designation, role_id, branch_id, created_at, updated_at FROM tbl_user ORDER BY name ASC");
    while ($row = $qr->fetch_assoc()) $users[] = $row;
    respond(200, ['status' => 'success', 'users' => $users]);

  // 10) List all permissions
  case 'list_permissions':
    $perms = [];
    $qr = $conn->query("SELECT id, role_id, module, permission FROM tbl_permission ORDER BY id ASC");
    while ($row = $qr->fetch_assoc()) $perms[] = $row;
    respond(200, ['status' => 'success', 'permissions' => $perms]);

  // 11) List all geofences
  case 'list_geofences':
    $geofences = [];
    $qr = $conn->query("SELECT id, name, center_lat, center_lng, radius_meters, created_at FROM tbl_geofence ORDER BY name ASC");
    while ($row = $qr->fetch_assoc()) $geofences[] = $row;
    respond(200, ['status' => 'success', 'geofences' => $geofences]);

  // 12) List all interest rates
  case 'list_interest_rates_full':
    $rates = [];
    $qr = $conn->query("SELECT id, annual_rate_percent, effective_date, created_at FROM tbl_interest_rate ORDER BY effective_date DESC");
    while ($row = $qr->fetch_assoc()) $rates[] = $row;
    respond(200, ['status' => 'success', 'rates' => $rates]);

  // 13) List all expenses
  case 'list_expenses':
    $expenses = [];
    $qr = $conn->query("SELECT id, description, expense_amount, branch_id, created_at, updated_at FROM tbl_expenses ORDER BY created_at DESC");
    while ($row = $qr->fetch_assoc()) $expenses[] = $row;
    respond(200, ['status' => 'success', 'expenses' => $expenses]);

  // 14) List all capital entries
  case 'list_capital':
    $capital = [];
    $qr = $conn->query("SELECT id, branch_id, amount, amount_used, amount_remaining, entry_type, description, created_at FROM tbl_capital ORDER BY created_at DESC");
    while ($row = $qr->fetch_assoc()) $capital[] = $row;
    respond(200, ['status' => 'success', 'capital' => $capital]);

  default:
    break;
}

// -----------------------------------------------------------
// AUTHENTICATED BORROWER ENDPOINTS
// -----------------------------------------------------------
$borrowerActions = [
  'get_profile','update_profile','change_password',
  'apply_loan','get_loans','get_schedule','make_payment','get_contacts','save_contacts',
  'upload_document','post_location','get_notifications','search_borrower','add_collateral', 
  'add_guarantor','logout', 'check_kyc_status','upload_kyc_record','link_card','unlink_card','link_bank','unlink_bank','get_linked_accounts',
  'add_support_message','get_support_messages'
];

if (in_array($action, $borrowerActions, true)) {
  $auth = authenticateBorrower();
  $borrowerId = $auth['borrower_id'];
  $token = $auth['token'];

  switch ($action) {

  // ---- NEW: Save Contacts from Mobile App ----
  case 'save_contacts':
    // Expects: contacts: [ {name, phone}, ... ]
    if (!isset($input['contacts']) || !is_array($input['contacts'])) {
        respond(422, ['status' => 'error', 'message' => 'Missing or invalid contacts array']);
    }
    $contacts = $input['contacts'];
    $saved = 0;
    foreach ($contacts as $contact) {
        if (!isset($contact['name']) || !isset($contact['phone'])) continue;
        $name = $conn->real_escape_string($contact['name']);
        $phone = $conn->real_escape_string($contact['phone']);
        // Prevent duplicates for this user
        $exists = $conn->query("SELECT id FROM tbl_user_contacts WHERE user_id=$borrowerId AND phone='$phone' LIMIT 1");
        if (!$exists->num_rows) {
            $conn->query("INSERT INTO tbl_user_contacts (user_id, name, phone) VALUES ($borrowerId, '$name', '$phone')");
            $saved++;
        }
    }
    respond(200, ['status' => 'success', 'saved' => $saved]);

  // ---- NEW: Get Contacts for User ----
  case 'get_contacts':
    $contacts = [];
    $qr = $conn->query("SELECT name, phone FROM tbl_user_contacts WHERE user_id=$borrowerId ORDER BY name ASC");
    while ($row = $qr->fetch_assoc()) {
        $contacts[] = $row;
    }
    respond(200, ['status' => 'success', 'contacts' => $contacts]);


    case 'get_profile':
      $stmt = $conn->prepare("
        SELECT id,name,email,nid,mobile,dob,address,gender,working_status,selfie_path
          FROM tbl_borrower
         WHERE id=?
      ");
      $stmt->bind_param("i",$borrowerId);
      $stmt->execute();
      respond(200,['status'=>'success','profile'=>$stmt->get_result()->fetch_assoc()]);

    case 'update_profile':
      $fields = []; $vals = [];
      foreach (['name','mobile','address','working_status'] as $f) {
        if (isset($input[$f])) {
          $fields[] = "`$f` = ?";
          $vals[]   = $conn->real_escape_string($input[$f]);
        }
      }
      if (empty($fields)) {
        respond(422,['status'=>'error','message'=>'No fields to update']);
      }
      $sql   = "UPDATE tbl_borrower SET ".implode(',', $fields)." WHERE id=?";
      $types = str_repeat('s', count($vals)) . 'i';
      $stmt = $conn->prepare($sql);
      if (!$stmt) respond(500,['status'=>'error','message'=>$conn->error]);
      $params = array_merge($vals, [ $borrowerId ]);
      $bindNames = [];
      $bindNames[] = $types;
      foreach ($params as $i => $value) {
        $bindNames[] = &$params[$i];
      }
      call_user_func_array([$stmt, 'bind_param'], $bindNames);
      $stmt->execute();
      respond(200,['status'=>'success','message'=>'Profile updated']);
                  
                  
            
              


case 'check_kyc_status':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $stmt = $conn->prepare("SELECT id FROM tbl_kyc_record WHERE borrower_id=? LIMIT 1");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
      respond(200, ['status' => 'success', 'kyc' => true]);
    } else {
      respond(200, ['status' => 'success', 'kyc' => false]);
    }
                  

    case 'change_password':
      if (empty($input['old_password'])||empty($input['new_password'])) {
        respond(422,['status'=>'error','message'=>'Both passwords required']);
      }
      $stmt = $conn->prepare("SELECT password_hash FROM tbl_borrower WHERE id=?");
      $stmt->bind_param("i",$borrowerId);
      $stmt->execute();
      $hash = $stmt->get_result()->fetch_assoc()['password_hash'];
      if (!password_verify($input['old_password'],$hash)) {
        respond(403,['status'=>'error','message'=>'Incorrect current password']);
      }
      $newHash = password_hash($input['new_password'],PASSWORD_BCRYPT);
      $stmt = $conn->prepare("
        UPDATE tbl_borrower
           SET password_hash=?
         WHERE id=?
      ");
      $stmt->bind_param("si",$newHash,$borrowerId);
      $stmt->execute();
      respond(200,['status'=>'success','message'=>'Password changed']);

    case 'apply_loan':
      foreach (['expected_amount','interest_rate_id','processing_fee_pct','installments'] as $f) {
        if (!isset($input[$f])) {
          respond(422,['status'=>'error','message'=>"Missing $f"]);
        }
      }
      $exp = (float)$input['expected_amount'];
      $rid = (int)$input['interest_rate_id'];
      $fee = (float)$input['processing_fee_pct'];
      $inst= (int)$input['installments'];
      $branchId = isset($input['branch_id']) ? (int)$input['branch_id'] : null;
      $stmt = $conn->prepare("
        SELECT annual_rate_percent
          FROM tbl_interest_rate
         WHERE id=? LIMIT 1
      ");
      $stmt->bind_param("i",$rid);
      $stmt->execute();
      $rr = $stmt->get_result()->fetch_assoc();
      if (!$rr) respond(404,['status'=>'error','message'=>'Rate not found']);
      $rp  = (float)$rr['annual_rate_percent'];
      $tot = round($exp*(1+$fee/100+$rp/100),2);
      $emi = round($tot/$inst,2);
      $stmt = $conn->prepare("
        INSERT INTO tbl_loan_application
          (borrower_id,branch_id,status,expected_amount,
           interest_rate_id,processing_fee_pct,installments,
           total_amount,emi_amount)
        VALUES(?,?,?,?,?,?,?,?,?)
      ");
      $st  = 'Pending';
      $stmt->bind_param("iisdiddii",
         $borrowerId,$branchId,$st,$exp,$rid,$fee,$inst,$tot,$emi
      );
      $stmt->execute();
      respond(201,['status'=>'success','loan_id'=>$conn->insert_id]);

    case 'get_loans':
      $stmt = $conn->prepare("
        SELECT * FROM tbl_loan_application
         WHERE borrower_id=?
      ");
      $stmt->bind_param("i",$borrowerId);
      $stmt->execute();
      $res = $stmt->get_result();
      $out = [];
      while ($r = $res->fetch_assoc()) $out[]=$r;
      respond(200,['status'=>'success','loans'=>$out]);

    case 'get_schedule':
      if (empty($input['loan_id'])) {
        respond(422,['status'=>'error','message'=>'Missing loan_id']);
      }
      $lid = (int)$input['loan_id'];
      $stmt = $conn->prepare("
        SELECT installment_no,due_date,due_amount,is_paid,paid_date
          FROM tbl_repayment_schedule
         WHERE loan_application_id=?
      ");
      $stmt->bind_param("i",$lid);
      $stmt->execute();
      $res = $stmt->get_result();
      $out = []; while ($r=$res->fetch_assoc()) $out[]=$r;
      respond(200,['status'=>'success','schedule'=>$out]);

    case 'make_payment':
      foreach (['loan_id','schedule_id','amount','installment_no'] as $f) {
        if (!isset($input[$f])) {
          respond(422,['status'=>'error','message'=>"Missing $f"]);
        }
      }
      $lid = (int)$input['loan_id'];
      $sid = (int)$input['schedule_id'];
      $amt = (float)$input['amount'];
      $ino = (int)$input['installment_no'];
      $dt  = date('Y-m-d');
      $stmt = $conn->prepare("
        INSERT INTO tbl_payment
          (borrower_id,loan_application_id,schedule_id,
           amount,payment_date,installment_no,remaining_installments)
        VALUES(?,?,?,?,?,?,(
          SELECT remaining_installments
            FROM tbl_loan_application WHERE id=?
        ))
      ");
      $stmt->bind_param("iiiisii",
        $borrowerId,$lid,$sid,$amt,$dt,$ino,$lid
      );
      $stmt->execute();
      $stmt = $conn->prepare("
        UPDATE tbl_repayment_schedule
           SET is_paid=1,paid_date=?
         WHERE id=?
      ");
      $stmt->bind_param("si",$dt,$sid);
      $stmt->execute();
      $stmt = $conn->prepare("
        UPDATE tbl_loan_application
           SET amount_paid=amount_paid+?,
               current_installment=current_installment+1
         WHERE id=?
      ");
      $stmt->bind_param("di",$amt,$lid);
      $stmt->execute();
      respond(200,['status'=>'success','message'=>'Payment recorded']);

    case 'upload_document':
      foreach (['file_base64','file_ext','loan_id','doc_type'] as $f) {
        if (empty($input[$f])) {
          respond(422,['status'=>'error','message'=>"Missing $f"]);
        }
      }
      $bin = base64_decode($input['file_base64']);
      if ($bin===false) {
        respond(400,['status'=>'error','message'=>'Invalid base64']);
      }
      $ext  = preg_replace('/[^a-z0-9]+/','',
                 strtolower($input['file_ext']));
      $lid  = (int)$input['loan_id'];
      $type = $conn->real_escape_string($input['doc_type']);
      $name = uniqid('doc_').".$ext";
      $path = "uploads/docs/$name";
      if (!file_put_contents($path,$bin)) {
        respond(500,['status'=>'error','message'=>'Could not save']);
      }
      $stmt = $conn->prepare("
        INSERT INTO tbl_document
          (borrower_id,loan_application_id,doc_type,file_path)
        VALUES(?,?,?,?)
      ");
      $stmt->bind_param("iiss",$borrowerId,$lid,$type,$path);
      $stmt->execute();
      respond(201,['status'=>'success','file_path'=>$path]);
 
// --- Link Card ---
case 'link_card':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $card_number = preg_replace('/\D/', '', $input['card_number'] ?? '');
    $expiry = $input['expiry'] ?? '';
    $cvv = $input['cvv'] ?? '';
    if (!$borrower_id || strlen($card_number) < 12 || !$expiry || !$cvv) {
        respond(422, ['status' => 'error', 'message' => 'Invalid card details']);
    }
    // Here, integrate with your payment provider to tokenize the card
    // Example: $card_token = tokenize_card_with_provider($card_number, $expiry, $cvv);
    // For demo, we'll just fake a token:
    $card_token = 'tok_' . substr(md5($card_number . $expiry), 0, 16);
    $last4 = substr($card_number, -4);
    $card_type = 'Card'; // Optionally detect type
    $stmt = $conn->prepare("
        INSERT INTO tbl_linked_card (borrower_id, card_number_last4, card_token, card_type, expiry)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $borrower_id, $last4, $card_token, $card_type, $expiry);
    $stmt->execute();
    respond(201, ['status' => 'success', 'message' => 'Card linked']);

// --- Unlink Card ---
case 'unlink_card':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $card_id = intval($input['card_id'] ?? 0);
    if (!$borrower_id || !$card_id) {
        respond(422, ['status' => 'error', 'message' => 'Missing card']);
    }
    $stmt = $conn->prepare("DELETE FROM tbl_linked_card WHERE id=? AND borrower_id=?");
    $stmt->bind_param("ii", $card_id, $borrower_id);
    $stmt->execute();
    respond(200, ['status' => 'success', 'message' => 'Card unlinked']);

// --- Link Bank ---
case 'link_bank':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $bank_name = $conn->real_escape_string($input['bank_name'] ?? '');
    $account_number = $conn->real_escape_string($input['account_number'] ?? '');
    if (!$borrower_id || !$bank_name || !$account_number) {
        respond(422, ['status' => 'error', 'message' => 'Invalid bank details']);
    }
    $stmt = $conn->prepare("
        INSERT INTO tbl_linked_bank (borrower_id, bank_name, account_number)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iss", $borrower_id, $bank_name, $account_number);
    $stmt->execute();
    respond(201, ['status' => 'success', 'message' => 'Bank linked']);

// --- Unlink Bank ---
case 'unlink_bank':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $bank_id = intval($input['bank_id'] ?? 0);
    if (!$borrower_id || !$bank_id) {
        respond(422, ['status' => 'error', 'message' => 'Missing bank']);
    }
    $stmt = $conn->prepare("DELETE FROM tbl_linked_bank WHERE id=? AND borrower_id=?");
    $stmt->bind_param("ii", $bank_id, $borrower_id);
    $stmt->execute();
    respond(200, ['status' => 'success', 'message' => 'Bank unlinked']);

// --- Get Linked Accounts ---
case 'get_linked_accounts':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $cards = [];
    $banks = [];
    $stmt = $conn->prepare("SELECT id, card_number_last4, card_type, expiry FROM tbl_linked_card WHERE borrower_id=?");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $cards[] = $r;
    $stmt = $conn->prepare("SELECT id, bank_name, account_number FROM tbl_linked_bank WHERE borrower_id=?");
    $stmt->bind_param("i", $borrower_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $banks[] = $r;
    respond(200, ['status' => 'success', 'cards' => $cards, 'banks' => $banks]);             

    case 'post_location':
      if (!isset($input['latitude'])||!isset($input['longitude'])) {
        respond(422,['status'=>'error','message'=>'lat/lng required']);
      }
      $lat = (float)$input['latitude'];
      $lng = (float)$input['longitude'];
      $dev = $conn->real_escape_string($input['device_id']??'');
      $stmt = $conn->prepare("
        INSERT INTO tbl_borrower_location
          (borrower_id,latitude,longitude,device_id)
        VALUES(?,?,?,?)
      ");
      $stmt->bind_param("idds",$borrowerId,$lat,$lng,$dev);
      $stmt->execute();
      respond(200,['status'=>'success','message'=>'Location logged']);

    case 'get_notifications':
      $stmt = $conn->prepare("
        SELECT id,type,message,is_read,sent_at 
          FROM tbl_notification 
         WHERE borrower_id=?
         ORDER BY sent_at DESC
      ");
      $stmt->bind_param("i",$borrowerId);
      $stmt->execute();
      $res = $stmt->get_result();
      $out = []; while ($r=$res->fetch_assoc()) $out[]=$r;
      respond(200,['status'=>'success','notifications'=>$out]);

    case 'search_borrower':
      if (empty($input['key'])) {
        respond(422,['status'=>'error','message'=>'search key required']);
      }
      $k = "%".$conn->real_escape_string($input['key'])."%";
      $stmt = $conn->prepare("
        SELECT b.id AS borrower_id,
               b.name,b.nid,b.mobile,
               l.id AS loan_id,l.status,l.total_amount
          FROM tbl_borrower b
          LEFT JOIN tbl_loan_application l 
            ON b.id=l.borrower_id
         WHERE b.nid LIKE ? OR b.mobile LIKE ?
      ");
      $stmt->bind_param("ss",$k,$k);
      $stmt->execute();
      $res = $stmt->get_result();
      $out = []; while ($r=$res->fetch_assoc()) $out[]=$r;
      respond(200,['status'=>'success','results'=>$out]);

    case 'add_collateral':
      foreach (['borrower_id','loan_application_id','property_name','property_details','market_value','outstanding_loan_value','expected_return_value','valuation_date'] as $f) {
        if (!isset($input[$f])) {
          respond(422,['status'=>'error','message'=>"Missing $f"]);
        }
      }
      $stmt = $conn->prepare("
        INSERT INTO tbl_liability
        (borrower_id, loan_application_id, property_name, property_details, market_value, outstanding_loan_value, expected_return_value, valuation_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("iissiiis",
        $input['borrower_id'],
        $input['loan_application_id'],
        $input['property_name'],
        $input['property_details'],
        $input['market_value'],
        $input['outstanding_loan_value'],
        $input['expected_return_value'],
        $input['valuation_date']
      );
      $stmt->execute();
      respond(201, ['status'=>'success','liability_id'=>$conn->insert_id]);

    case 'add_guarantor':
      foreach (['loan_application_id','name','relationship','contact'] as $f) {
        if (!isset($input[$f])) {
          respond(422,['status'=>'error','message'=>"Missing $f"]);
        }
      }
      $stmt = $conn->prepare("
        INSERT INTO tbl_guarantor
        (loan_application_id, name, relationship, contact, nid, address)
        VALUES (?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param("isssis",
        $input['loan_application_id'],
        $input['name'],
        $input['relationship'],
        $input['contact'],
        $input['nid'],
        $input['address']
      );
      $stmt->execute();
      respond(201, ['status'=>'success','guarantor_id'=>$conn->insert_id]);

    case 'logout':
      $stmt = $conn->prepare("DELETE FROM tbl_borrower_tokens WHERE token=? AND borrower_id=?");
      $stmt->bind_param("si", $token, $borrowerId);
      $stmt->execute();
      respond(200, ['status' => 'success', 'message' => 'Logged out successfully']);
                  
                  
 

case 'upload_kyc_record':
    $borrower_id = intval($input['borrower_id'] ?? 0);
    $scanned_data = $input['scanned_data'] ?? '';
    $front_image_base64 = $input['front_image_base64'] ?? '';
    $back_image_base64 = $input['back_image_base64'] ?? '';
    $selfie_base64 = $input['selfie_base64'] ?? '';

    if (
        !$borrower_id ||
        !$scanned_data ||
        !$front_image_base64 ||
        !$back_image_base64 ||
        !$selfie_base64
    ) {
        respond(422, ['status' => 'error', 'message' => 'Missing required fields']);
    }

    // Ensure directories exist
    $frontDir = "admin/uploads/kyc/front";
    $backDir = "admin/uploads/kyc/back";
    $selfieDir = "admin/uploads/kyc/selfie";
    if (!is_dir($frontDir)) mkdir($frontDir, 0777, true);
    if (!is_dir($backDir)) mkdir($backDir, 0777, true);
    if (!is_dir($selfieDir)) mkdir($selfieDir, 0777, true);

    // Save images
    $timestamp = time();
    $front_path = "$frontDir/front_{$borrower_id}_$timestamp.jpg";
    $back_path = "$backDir/back_{$borrower_id}_$timestamp.jpg";
    $selfie_path = "$selfieDir/selfie_{$borrower_id}_$timestamp.jpg";

    file_put_contents($front_path, base64_decode($front_image_base64));
    file_put_contents($back_path, base64_decode($back_image_base64));
    file_put_contents($selfie_path, base64_decode($selfie_base64));

    // Prepare scanned_data as JSON
    $scanned_json = null;
    $decoded = json_decode($scanned_data, true);
    if (is_array($decoded)) {
        $scanned_json = json_encode($decoded);
    } else {
        $scanned_json = json_encode(['raw' => $scanned_data]);
    }

    // Insert into tbl_kyc_record
    $stmt = $conn->prepare("
        INSERT INTO tbl_kyc_record
        (borrower_id, scanned_data, front_image_path, back_image_path, selfie_path, status)
        VALUES (?, ?, ?, ?, ?, 'Pending')
    ");
    $stmt->bind_param(
        "issss",
        $borrower_id,
        $scanned_json,
        $front_path,
        $back_path,
        $selfie_path
    );
    if ($stmt->execute()) {
        respond(201, ['status' => 'success', 'message' => 'KYC record uploaded']);
    } else {
        respond(500, ['status' => 'error', 'message' => 'Failed to save KYC record']);
    }
                  
   
    // --- Support Message: Add ---
    case 'add_support_message':
      $msg = trim($input['message'] ?? '');
      if ($msg === '') {
        respond(422, ['status' => 'error', 'message' => 'Message required']);
      }
      // Find all managers (role_id=1)
      $managers = [];
      $qr = $conn->query("SELECT id FROM tbl_user WHERE role_id=1");
      while ($row = $qr->fetch_assoc()) $managers[] = (int)$row['id'];
      if (empty($managers)) {
        respond(500, ['status' => 'error', 'message' => 'No managers found']);
      }
      $stmt = $conn->prepare("INSERT INTO tbl_support_message (borrower_id, manager_user_id, sender_type, message) VALUES (?, ?, 'borrower', ?)");
      $inserted = 0;
      foreach ($managers as $mid) {
        $stmt->bind_param("iis", $borrowerId, $mid, $msg);
        if ($stmt->execute()) $inserted++;
      }
      respond(201, ['status' => 'success', 'inserted' => $inserted]);

    // --- Support Message: Get ---
    case 'get_support_messages':
      // Fetch all messages for this borrower, from all managers
      $stmt = $conn->prepare("SELECT id, borrower_id, manager_user_id, sender_type, message, sent_at FROM tbl_support_message WHERE borrower_id=? ORDER BY sent_at ASC, id ASC");
      $stmt->bind_param("i", $borrowerId);
      $stmt->execute();
      $res = $stmt->get_result();
      $messages = [];
      while ($row = $res->fetch_assoc()) $messages[] = $row;
      respond(200, ['status' => 'success', 'messages' => $messages]);

    default:
      break;
  }
}

// -----------------------------------------------------------
// REMOVE ADMIN/OFFICER ENDPOINTS
// -----------------------------------------------------------
// Fallback
respond(400,['status'=>'error','message'=>'Unknown or unauthorized action']);