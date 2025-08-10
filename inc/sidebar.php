<div class="container-fluid">
  <div class="row row-offcanvas row-offcanvas-right">
    <nav class="bg-white sidebar sidebar-offcanvas" id="sidebar">
      <div class="user-info text-center py-4">
        <img src="images/user.png" alt="User Image"
             class="mb-2" style="width:60px; height:60px; border-radius:50%;">
        <p class="name mb-0 font-weight-bold">
          <?php echo $_SESSION["userlogin"]; ?>
        </p>
        <p class="designation text-muted mb-1">
          <?php echo $_SESSION["designation"]; ?>
        </p>
        <span class="online"></span>
      </div>

      <?php $role = (int)$_SESSION["role_id"]; ?>

      <ul class="nav flex-column">
        <!-- Dashboard: everyone -->
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="index.php">
            <i class="fas fa-tachometer-alt mr-2"></i>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>

        <!-- Loan Verification: head (2) & verifier (3) -->
        <?php if ($role === 2 || $role === 3): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="loanverify.php">
            <i class="fas fa-clipboard-check mr-2"></i>
            <span class="menu-title">Loan Verification</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Borrowers -->
        <?php if ($role === 1 || $role === 2): ?>
          <!-- branch & head: Add + View -->
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center"
               data-toggle="collapse" href="#borrowerSubmenu" aria-expanded="false">
              <i class="fas fa-users mr-2"></i>
              <span class="menu-title">Borrowers</span>
              <i class="fa fa-sort-down ml-1"></i>
            </a>
            <div class="collapse" id="borrowerSubmenu">
              <ul class="nav flex-column sub-menu ml-4">
                <li class="nav-item">
                  <a class="nav-link" href="addborrower.php">
                    <i class="fas fa-user-plus mr-2"></i>Add Borrower
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="viewborrower.php">
                    <i class="fas fa-address-book mr-2"></i>View Borrowers
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" href="today_borrowers.php">
                    <i class="fas fa-calendar-day mr-2"></i>Today's Borrowers
                  </a>
                </li>
              </ul>
            </div>
          </li>
        <?php elseif ($role === 3): ?>
          <!-- verifier: View only -->
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="viewborrower.php">
              <i class="fas fa-address-book mr-2"></i>
              <span class="menu-title">View Borrowers</span>
            </a>
          </li>
        <?php endif; ?>

        <!-- Loans: branch & head -->
        <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center"
             data-toggle="collapse" href="#loanSubmenu" aria-expanded="false">
            <i class="fas fa-money-check-alt mr-2"></i>
            <span class="menu-title">Loans</span>
            <i class="fa fa-sort-down ml-1"></i>
          </a>
          <div class="collapse" id="loanSubmenu">
            <ul class="nav flex-column sub-menu ml-4">
              <li class="nav-item">
                <a class="nav-link" href="apply_for_loan.php">
                  <i class="fas fa-file-signature mr-2"></i>Apply Loan
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="payloan.php">
                  <i class="fas fa-money-bill-wave mr-2"></i>Pay Loan
                </a>
              </li>
               <li class="nav-item">
                <a class="nav-link" href="update_borrower_balance.php">
                  <i class="fas fa-money-bill-wave mr-2"></i>Edit Loan balance
                </a>
              </li>
            </ul>
          </div>
        </li>
        <?php endif; ?>

        <!-- Location: branch & head -->
        <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="locate_borrower.php">
            <i class="fas fa-map-marker-alt mr-2"></i>
            <span class="menu-title">Locate Borrower</span>
          </a>
        </li>
        <?php endif; ?>
              
              <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="terms_conditions.php">
            <i class="fas fa-file-contract mr-2"></i>
            <span class="menu-title">Terms and Conditions</span>
          </a>
        </li>
        <?php endif; ?>
              
         <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="interest_rates.php">
            <i class="fas fa-money-bill-wave mr-2"></i>
            <span class="menu-title">Interest Interests</span>
          </a>
        </li>
        <?php endif; ?>      

        <!-- Expenses: branch & head -->
        <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="expenses.php">
            <i class="fas fa-file-invoice-dollar mr-2"></i>
            <span class="menu-title">Expenses</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Reports: branch & head -->
        <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="reports.php">
            <i class="fas fa-chart-bar mr-2"></i>
            <span class="menu-title">Generate Reports</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Notifications: branch & head -->
        <?php if ($role === 1 || $role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="notification.php">
            <i class="fas fa-bell mr-2"></i>
            <span class="menu-title">Notifications</span>
          </a>
        </li>
        <?php endif; ?>

        <!-- Head Officer Only (role=2) -->
        <?php if ($role === 2): ?>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="managebranch.php">
            <i class="fas fa-code-branch mr-2"></i>
            <span class="menu-title">Manage Branches</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="capital.php">
            <i class="fas fa-coins mr-2"></i>
            <span class="menu-title">Start Amount</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link d-flex align-items-center" href="branchofficers.php">
            <i class="fas fa-user-shield mr-2"></i>
            <span class="menu-title">Manage Branch Officers</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </nav>
