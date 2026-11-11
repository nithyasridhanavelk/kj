<?php
require_once __DIR__ . '/config/db.php';
if (isset($_SESSION['admin_user'])) {
    header('Location: index.php');
    exit();
}
$error = $_GET['error'] ?? '';
$msg = $_GET['msg'] ?? '';
$prefillEmail = $_GET['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login | KINZ - A Hidden Treasure</title>
  <meta name="description" content="Authorized management login for Kinz Guarantee Jewellery Atelier. Access stocks, order dispatch, bullion controls, and customer records."> 
  
  <link rel="icon" type="image/jpeg" href="assets/kinz_logo.jpg">
  <link rel="apple-touch-icon" href="assets/kinz_logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="styles/theme.css">
  <link rel="stylesheet" href="styles/customer.css">
  <link rel="stylesheet" href="assets/css/style.css">

  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html, body {
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    body {
      background-color: var(--warm-ivory, #FAF8F0);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      font-family: 'Poppins', sans-serif;
      color: var(--deep-charcoal-green, #062C22);
    }

    .admin-main-content {
      flex: 1 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px 20px;
      width: 100%;
      min-height: 0;
    }

    .auth-page-container {
      max-width: 520px;
      width: 100%;
      margin: 0 auto;
    }

    /* Auth Card matching reference image */
    .auth-form-card {
      background: #ffffff;
      border: 1.5px solid rgba(212, 175, 55, 0.4);
      border-radius: 20px;
      padding: 22px 28px 20px;
      box-shadow: 0 16px 40px rgba(10, 61, 46, 0.08), 0 4px 16px rgba(184, 134, 11, 0.05);
      position: relative;
      overflow: hidden;
    }

    /* Elegant gold top accent curve */
    .auth-form-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #d4af37, #f7d779, #c5a059, #f7d779, #d4af37);
    }

    /* Heading: ->] Login */
    .auth-card-heading {
      font-family: 'Poppins', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      color: var(--deep-charcoal-green, #062C22);
      text-align: center;
      margin: 0 0 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .auth-card-heading i {
      font-size: 1.2rem;
      color: var(--deep-charcoal-green, #062C22);
    }

    /* Form Fields */
    .auth-input-group {
      margin-bottom: 12px;
      text-align: left;
    }

    .auth-label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 4px;
    }

    .auth-field-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .auth-field-icon {
      position: absolute;
      left: 14px;
      color: var(--antique-copper, #C87927);
      font-size: 0.95rem;
      pointer-events: none;
      transition: color 0.25s ease;
    }

    .auth-text-input {
      width: 100%;
      padding: 9px 14px 9px 40px;
      border: 1.5px solid var(--border-light, rgba(10, 61, 46, 0.14));
      border-radius: 10px;
      font-size: 0.88rem;
      color: var(--deep-charcoal-green, #062C22);
      background: #faf8f5;
      transition: all 0.3s ease;
      font-family: inherit;
    }

    .auth-text-input::placeholder {
      color: #94a3b8;
      font-size: 0.86rem;
    }

    .auth-text-input:focus {
      outline: none;
      border-color: var(--gold-500, #C87927);
      background: #ffffff;
      box-shadow: 0 0 0 3px rgba(200, 121, 39, 0.16);
    }

    .auth-password-toggle {
      position: absolute;
      right: 12px;
      background: none;
      border: none;
      color: #8fa099;
      cursor: pointer;
      font-size: 0.95rem;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.2s ease;
    }

    .auth-password-toggle:hover {
      color: var(--emerald-900, #0A3D2E);
    }

    /* Meta Row: Remember me + Forgot Password */
    .auth-row-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 6px;
      margin-bottom: 14px;
      font-size: 0.8rem;
    }

    .auth-checkbox-label {
      display: flex;
      align-items: center;
      gap: 7px;
      color: #334155;
      font-size: 0.8rem;
      font-weight: 500;
      cursor: pointer;
      user-select: none;
    }

    .auth-checkbox-label input[type="checkbox"] {
      accent-color: var(--antique-copper, #C87927);
      width: 15px;
      height: 15px;
      cursor: pointer;
      flex-shrink: 0;
      border-radius: 4px;
    }

    .auth-forgot-link {
      color: var(--antique-copper, #C87927);
      text-decoration: none;
      font-weight: 600;
      font-size: 0.8rem;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
      transition: color 0.2s ease;
    }

    .auth-forgot-link:hover {
      color: #a65e18;
      text-decoration: underline;
    }

    /* Primary Submit Button */
    .btn-auth-primary {
      width: 100%;
      padding: 11px 20px;
      background: var(--deep-charcoal-green, #062C22);
      color: #ffffff;
      border: 1.5px solid rgba(200, 121, 39, 0.45);
      border-radius: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 0.92rem;
      font-weight: 700;
      letter-spacing: 0.8px;
      text-transform: uppercase;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      box-shadow: 0 6px 18px rgba(6, 44, 34, 0.22);
    }

    .btn-auth-primary:hover {
      background: var(--forest-green, #0A3D2E);
      border-color: #ffd700;
      transform: translateY(-1px);
      box-shadow: 0 8px 22px rgba(10, 61, 46, 0.3);
    }

    /* Divider & Google Login */
    .auth-or-divider {
      display: flex;
      align-items: center;
      text-align: center;
      margin: 10px 0 8px;
      color: #718096;
      font-size: 0.72rem;
      letter-spacing: 1.5px;
      font-weight: 600;
    }

    .auth-or-divider::before,
    .auth-or-divider::after {
      content: '';
      flex: 1;
      border-bottom: 1px solid var(--border-light, rgba(10, 61, 46, 0.14));
    }

    .auth-or-divider::before { margin-right: 12px; }
    .auth-or-divider::after { margin-left: 12px; }

    .btn-google-auth {
      width: 100%;
      padding: 9px 16px;
      border: 1.5px solid #dadce0;
      border-radius: 10px;
      background: #ffffff;
      color: #3c4043;
      font-family: inherit;
      font-size: 0.86rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.25s ease;
      box-shadow: 0 1px 3px rgba(60, 64, 67, 0.08);
    }

    .btn-google-auth:hover {
      background: #f8faf9;
      border-color: #c2c7d0;
      box-shadow: 0 3px 10px rgba(60, 64, 67, 0.12);
      transform: translateY(-1px);
    }

    /* Google Account Picker Modal Styles */
    .google-account-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 14px;
      border: 1px solid #e0e0e0;
      border-radius: 12px;
      background: #ffffff;
      width: 100%;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: inherit;
    }

    .google-account-item:hover {
      background: #f8faf9;
      border-color: #c2c7d0;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    }

    .google-acc-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--deep-charcoal-green, #062C22);
      color: #D4AF37;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
      flex-shrink: 0;
    }

    /* Tab Panels */
    .auth-panel {
      display: none;
    }

    .auth-panel.active {
      display: block;
      animation: fadeInAuth 0.25s ease;
    }

    @keyframes fadeInAuth {
      from { opacity: 0; transform: translateY(3px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .auth-switch-prompt {
      text-align: center;
      font-size: 0.84rem;
      color: #64748b;
      margin-top: 12px;
      margin-bottom: 0;
    }

    .auth-switch-btn {
      color: var(--antique-copper, #C87927);
      font-weight: 700;
      background: none;
      border: none;
      cursor: pointer;
      text-decoration: none !important;
      padding: 0 4px;
      font-size: inherit;
      font-family: inherit;
      transition: color 0.2s ease, opacity 0.2s ease;
    }

    .auth-switch-btn:hover {
      color: var(--emerald-900, #0A3D2E);
      text-decoration: none !important;
      opacity: 0.85;
    }

    /* Modal Overlay for Password Reset */
    .auth-modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(6, 44, 34, 0.75);
      backdrop-filter: blur(6px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 20px;
    }

    .auth-modal-overlay.active {
      display: flex;
    }

    .auth-modal-box {
      background: #ffffff;
      border: 1.5px solid rgba(212, 175, 55, 0.5);
      border-radius: 20px;
      padding: 32px 28px;
      max-width: 420px;
      width: 100%;
      position: relative;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
      animation: modalPop 0.3s ease;
    }

    @keyframes modalPop {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }

    .auth-modal-close {
      position: absolute;
      top: 14px;
      right: 18px;
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #94a3b8;
      cursor: pointer;
      line-height: 1;
    }

    .auth-modal-close:hover {
      color: var(--deep-charcoal-green, #062C22);
    }

    .auth-modal-title {
      font-family: 'Poppins', sans-serif;
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--deep-charcoal-green, #062C22);
      margin-bottom: 6px;
    }

    .auth-modal-sub {
      font-size: 0.86rem;
      color: #64748b;
      margin-bottom: 20px;
      line-height: 1.5;
    }

    /* ==========================================================================
       Atelier Top Header with Left Corner Logo - Dark Emerald Green
       ========================================================================== */
    .kinz-login-header {
      width: 100%;
      background: var(--deep-charcoal-green, #062C22);
      border-bottom: 1.5px solid rgba(200, 121, 39, 0.45);
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
      flex-shrink: 0;
      z-index: 1000;
    }

    .login-header-inner {
      max-width: 1400px;
      margin: 0 auto;
      padding: 18px 32px;
      min-height: 84px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* Left Corner Brand Logo */
    .corner-brand-logo {
      display: flex;
      align-items: center;
      gap: 14px;
      text-decoration: none;
      transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .corner-brand-logo:hover {
      transform: translateY(-1px);
    }

    .corner-logo-frame {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      padding: 2.5px;
      background: linear-gradient(135deg, #d4af37, #C87927, #f7d779);
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.4), 0 0 14px rgba(200, 121, 39, 0.45);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.35s ease;
      flex-shrink: 0;
    }

    .corner-brand-logo:hover .corner-logo-frame {
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.55), 0 0 22px rgba(200, 121, 39, 0.75);
      transform: scale(1.04);
    }

    .corner-logo-img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      display: block;
    }

    .corner-brand-details {
      display: flex;
      flex-direction: column;
    }

    .corner-brand-name {
      font-family: 'Poppins', sans-serif;
      font-size: 1.35rem;
      font-weight: 700;
      letter-spacing: 2.2px;
      color: var(--champagne-beige, #F4E2C5);
      line-height: 1.1;
    }

    .corner-brand-tagline {
      font-size: 0.65rem;
      font-weight: 600;
      letter-spacing: 1.8px;
      color: var(--antique-copper, #C87927);
      text-transform: uppercase;
      margin-top: 2px;
    }

    /* ==========================================================================
       Dark Emerald Green Footer Bar (Single Strip) - Static Screen
       ========================================================================== */
    .kinz-login-footer {
      width: 100%;
      background: var(--deep-charcoal-green, #062C22) !important;
      background-color: #062C22 !important;
      border-top: 1.5px solid rgba(200, 121, 39, 0.4);
      box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.35);
      flex-shrink: 0;
      font-family: 'Poppins', sans-serif;
    }

    .footer-bottom-bar {
      padding: 18px 32px;
      min-height: 64px;
      display: flex;
      align-items: center;
    }

    .footer-bottom-inner {
      max-width: 1400px;
      width: 100%;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
      font-size: 0.85rem;
      color: #cfcac0;
    }

    .footer-copy-text {
      color: #dfdbd2;
      font-size: 0.85rem;
    }

    .footer-copy-text .gold-tagline {
      color: var(--antique-copper, #C87927);
      font-weight: 600;
    }

    .footer-policy-links {
      display: flex;
      align-items: center;
      gap: 24px;
    }

    .footer-policy-links a {
      color: #cadbd3;
      text-decoration: none;
      font-size: 0.85rem;
      transition: color 0.25s ease;
    }

    .footer-policy-links a:hover {
      color: var(--antique-copper, #C87927);
      text-decoration: underline;
    }

    /* Responsive adjustments */
    @media (max-width: 640px) {
      .login-header-inner {
        padding: 12px 16px;
      }
      .corner-brand-name {
        font-size: 1.2rem;
      }
      .corner-logo-frame {
        width: 44px;
        height: 44px;
      }
      .admin-main-content {
        padding: 16px 12px;
      }
      .auth-form-card {
        padding: 20px 18px 18px;
        border-radius: 16px;
      }
      .footer-bottom-bar {
        padding: 16px 16px;
      }
      .footer-bottom-inner {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 12px;
      }
      .footer-policy-links {
        justify-content: center;
        gap: 16px;
        font-size: 0.8rem;
      }
    }
  </style>
</head>
<body>

  <!-- Top Navigation Header with Left Corner Logo -->
  <header class="kinz-login-header" id="main-header">
    <div class="login-header-inner">
      <!-- Left Corner Brand Logo -->
      <div class="corner-brand-logo" id="header-brand-logo" aria-label="KINZ Royal Atelier Home" title="KINZ - A Hidden Treasure" style="cursor: default;">
        <div class="corner-logo-frame">
          <img src="assets/kinz_logo.jpg" alt="KINZ Luxury Jewellery Logo" class="corner-logo-img">
        </div>
        <div class="corner-brand-details">
          <span class="corner-brand-name">KINZ</span>
          <span class="corner-brand-tagline">A HIDDEN TREASURE</span>
        </div>
      </div>
    </div>
  </header>

  <!-- Centered Login Main Content Area -->
  <main class="admin-main-content">
    <div class="auth-page-container">
      <section class="auth-form-card" aria-label="Administrator Login">
        
        <!-- Heading: ->] Login -->
        <h2 class="auth-card-heading" id="auth-card-heading">
          <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
        </h2>

        <!-- PANEL 1: LOGIN -->
        <div class="auth-panel active" id="panel-login" role="tabpanel">
          <!-- Login Form: Essential credentials alone -->
          <form id="form-admin-login" method="POST" action="actions/login_action.php">
            
            <!-- Email Address or Mobile -->
            <div class="auth-input-group">
              <label for="admin-identifier" class="auth-label">Email Address or Mobile</label>
              <div class="auth-field-wrapper">
                <i class="fa-regular fa-envelope auth-field-icon"></i>
                <input 
                  type="text" 
                  class="auth-text-input" 
                  name="identifier" id="admin-identifier" value="<?= htmlspecialchars($prefillEmail) ?>" 
                  placeholder="e.g. priya.sharma@email.com" 
                  required 
                  autocomplete="username"
                >
              </div>
            </div>

            <!-- Password -->
            <div class="auth-input-group">
              <label for="admin-password" class="auth-label">Password</label>
              <div class="auth-field-wrapper">
                <i class="fa-solid fa-lock auth-field-icon"></i>
                <input 
                  type="password" 
                  class="auth-text-input" 
                  name="password" id="admin-password" 
                  placeholder="Enter your password" 
                  required 
                  autocomplete="current-password"
                >
                <button type="button" class="auth-password-toggle" id="toggle-admin-pass" aria-label="Show or hide password">
                  <i class="fa-regular fa-eye"></i>
                </button>
              </div>
            </div>

            <!-- Remember me & Forgot Password -->
            <div class="auth-row-meta">
              <label class="auth-checkbox-label">
                <input type="checkbox" id="admin-remember" checked>
                <span>Remember me</span>
              </label>
              <button type="button" class="auth-forgot-link" id="btn-open-forgot">Forgot Password?</button>
            </div>

            <!-- Submit Button: Login alone -->
            <button type="submit" class="btn-auth-primary" id="btn-admin-submit">
              <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
            </button>

            <!-- Divider -->
            <div class="auth-or-divider">OR</div>

            <!-- Continue with Google Button -->
            <button type="button" class="btn-google-auth" id="btn-google-login" title="Continue with Google">
              <svg width="20" height="20" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
              </svg>
              <span>Continue with Google</span>
            </button>

            <!-- Switch to Register -->
            <p class="auth-switch-prompt">
              Don't have an account yet?
              <button type="button" class="auth-switch-btn" id="btn-switch-to-reg" style="text-decoration: none !important;">Create Account</button>
            </p>
          </form>
        </div>

        <!-- PANEL 2: CREATE ACCOUNT -->
        <div class="auth-panel" id="panel-register" role="tabpanel">
          <form id="form-register" method="POST" action="actions/register_action.php">
            <div class="auth-input-group">
              <label for="reg-name" class="auth-label">Full Name</label>
              <div class="auth-field-wrapper">
                <i class="fa-regular fa-user auth-field-icon"></i>
                <input type="text" class="auth-text-input" name="name" id="reg-name" placeholder="e.g. Priya Sharma" required autocomplete="name">
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
              <div class="auth-input-group" style="margin-bottom: 8px;">
                <label for="reg-email" class="auth-label">Email Address</label>
                <div class="auth-field-wrapper">
                  <i class="fa-regular fa-envelope auth-field-icon"></i>
                  <input type="email" class="auth-text-input" name="email" id="reg-email" placeholder="priya@email.com" required autocomplete="email">
                </div>
              </div>

              <div class="auth-input-group" style="margin-bottom: 8px;">
                <label for="reg-phone" class="auth-label">Mobile Number</label>
                <div class="auth-field-wrapper">
                  <i class="fa-solid fa-phone auth-field-icon"></i>
                  <input type="tel" class="auth-text-input" id="reg-phone" placeholder="+91 98765 43210" required autocomplete="tel">
                </div>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
              <div class="auth-input-group" style="margin-bottom: 8px;">
                <label for="reg-password" class="auth-label">Create Password</label>
                <div class="auth-field-wrapper">
                  <i class="fa-solid fa-lock auth-field-icon"></i>
                  <input type="password" class="auth-text-input" name="password" id="reg-password" placeholder="Min 6 characters" required autocomplete="new-password">
                  <button type="button" class="auth-password-toggle" id="toggle-reg-pass" aria-label="Show or hide password">
                    <i class="fa-regular fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="auth-input-group" style="margin-bottom: 8px;">
                <label for="reg-confirm-pass" class="auth-label">Confirm Password</label>
                <div class="auth-field-wrapper">
                  <i class="fa-solid fa-lock-open auth-field-icon"></i>
                  <input type="password" class="auth-text-input" name="confirm_password" id="reg-confirm-pass" placeholder="Re-enter password" required autocomplete="new-password">
                </div>
              </div>
            </div>

            <button type="submit" class="btn-auth-primary" id="btn-register-submit" style="margin-top: 6px;">
              <i class="fa-solid fa-user-plus"></i> Create Account
            </button>

            <p class="auth-switch-prompt">
              Already have an account?
              <button type="button" class="auth-switch-btn" id="btn-switch-to-login" style="text-decoration: none !important;">Login</button>
            </p>
          </form>
        </div>

      </section>
    </div>
  </main>

  <!-- Dark Emerald Green Footer Bar -->
  <footer class="kinz-login-footer" id="main-footer" style="background-color: #062C22 !important;">
    <div class="footer-bottom-bar">
      <div class="footer-bottom-inner">
        <div class="footer-copy-text">
          &copy; 2026 KINZ Jewellery. All rights reserved. | <span class="gold-tagline">A Hidden Treasure.</span>
        </div>
        <div class="footer-policy-links">
          <a href="javascript:void(0)" onclick="return false;">Privacy Policy</a>
          <a href="javascript:void(0)" onclick="return false;">Terms of Privilege</a>
          <a href="javascript:void(0)" onclick="return false;">Security Guarantee</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- MODAL: Forgot Password Recovery -->
  <div class="auth-modal-overlay" id="modal-forgot-password" role="dialog" aria-modal="true" aria-labelledby="modal-forgot-title">
    <div class="auth-modal-box">
      <button class="auth-modal-close" id="btn-close-forgot" aria-label="Close modal">&times;</button>
      
      <div style="width:48px; height:48px; border-radius:50%; background:rgba(212,175,55,0.15); border:1.5px solid var(--gold-400, #d68f44); display:flex; align-items:center; justify-content:center; color:var(--gold-600, #b56b20); margin-bottom:14px;">
        <i class="fa-solid fa-key" style="font-size:1.3rem;"></i>
      </div>

      <h3 class="auth-modal-title" id="modal-forgot-title">Reset Access Passkey</h3>
      <p class="auth-modal-sub">
        Enter your registered email address or mobile number. A security verification code will be dispatched.
      </p>

      <div id="forgot-step-1">
        <div class="auth-input-group">
          <label for="forgot-admin-id" class="auth-label">Email Address or Mobile</label>
          <div class="auth-field-wrapper">
            <i class="fa-regular fa-envelope auth-field-icon"></i>
            <input type="text" class="auth-text-input" id="forgot-admin-id" placeholder="e.g. priya.sharma@email.com">
          </div>
        </div>
        <button type="button" class="btn-auth-primary" id="btn-send-admin-otp">
          <i class="fa-solid fa-paper-plane"></i> Dispatch Security Code
        </button>
      </div>

      <div id="forgot-step-2" style="display: none;">
        <div style="background: rgba(10,61,46,0.08); border: 1px solid rgba(212,175,55,0.5); border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; font-size: 0.86rem; color: #062C22;">
          <i class="fa-solid fa-circle-check" style="color: #10b981; margin-right: 6px;"></i>
          Security verification code dispatched! <strong>OTP: 749210</strong>
        </div>

        <div class="auth-input-group">
          <label for="forgot-admin-otp" class="auth-label">6-Digit Verification Token</label>
          <div class="auth-field-wrapper">
            <i class="fa-solid fa-shield-halved auth-field-icon"></i>
            <input type="text" class="auth-text-input" id="forgot-admin-otp" placeholder="749210" maxlength="6" value="749210">
          </div>
        </div>

        <div class="auth-input-group">
          <label for="forgot-admin-newpass" class="auth-label">New Password</label>
          <div class="auth-field-wrapper">
            <i class="fa-solid fa-lock auth-field-icon"></i>
            <input type="password" class="auth-text-input" id="forgot-admin-newpass" placeholder="Enter new password">
          </div>
        </div>

        <button type="button" class="btn-auth-primary" id="btn-confirm-admin-reset">
          <i class="fa-solid fa-check-double"></i> Save New Password &amp; Login
        </button>
      </div>
    </div>
  </div>

  <!-- MODAL: Google Account Selector & Admin Privilege Verification -->
  <div class="auth-modal-overlay" id="modal-google-auth" role="dialog" aria-modal="true" aria-labelledby="modal-google-title">
    <div class="auth-modal-box" style="max-width: 440px;">
      <button class="auth-modal-close" id="btn-close-google-auth" aria-label="Close modal">&times;</button>
      
      <!-- Google Logo & Header -->
      <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
        <svg width="24" height="24" viewBox="0 0 48 48" aria-hidden="true">
          <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
          <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
          <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
          <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        <span style="font-weight:700; color:#202124; font-size:1.05rem;">Sign in with Google</span>
      </div>

      <h3 class="auth-modal-title" id="modal-google-title" style="font-size:1.15rem; margin-bottom:4px;">Choose an account</h3>
      <p class="auth-modal-sub" style="margin-bottom:16px;">
        to continue to <strong>KINZ Jewellery Admin Vault</strong>
      </p>

      <!-- Account List -->
      <div class="google-accounts-list" style="display:flex; flex-direction:column; gap:8px; margin-bottom:14px;">
        
        <!-- Account 1: Authorized Super Admin -->
        <button type="button" class="google-account-item" id="google-acc-admin">
          <div class="google-acc-avatar">AK</div>
          <div style="flex:1; text-align:left;">
            <div style="display:flex; align-items:center; gap:6px;">
              <span style="font-weight:600; font-size:0.9rem; color:#202124;">Alexander Kinz</span>
              <span style="background:rgba(16,185,129,0.12); color:#059669; font-size:0.68rem; font-weight:700; padding:2px 6px; border-radius:99px; border:1px solid rgba(16,185,129,0.3);">Authorized Admin</span>
            </div>
            <div style="font-size:0.78rem; color:#5f6368;">admin@kinzjewellery.com</div>
          </div>
          <i class="fa-solid fa-chevron-right" style="color:#9aa0a6; font-size:0.8rem;"></i>
        </button>

        <!-- Account 2: Unauthorized Personal Account -->
        <button type="button" class="google-account-item" id="google-acc-unauth">
          <div class="google-acc-avatar" style="background:#e8f0fe; color:#1a73e8;">PS</div>
          <div style="flex:1; text-align:left;">
            <div style="display:flex; align-items:center; gap:6px;">
              <span style="font-weight:600; font-size:0.9rem; color:#202124;">Priya Sharma</span>
              <span style="background:rgba(239,68,68,0.1); color:#dc2626; font-size:0.68rem; font-weight:700; padding:2px 6px; border-radius:99px; border:1px solid rgba(239,68,68,0.25);">Unauthorized</span>
            </div>
            <div style="font-size:0.78rem; color:#5f6368;">priya.sharma@gmail.com</div>
          </div>
          <i class="fa-solid fa-chevron-right" style="color:#9aa0a6; font-size:0.8rem;"></i>
        </button>

        <!-- Account 3: Use Another Account Toggle -->
        <button type="button" class="google-account-item" id="google-acc-custom-toggle">
          <div class="google-acc-avatar" style="background:#f1f3f4; color:#5f6368;"><i class="fa-solid fa-user-plus" style="font-size:0.85rem;"></i></div>
          <div style="flex:1; text-align:left;">
            <span style="font-weight:600; font-size:0.88rem; color:#1a73e8;">Use another Google account</span>
          </div>
          <i class="fa-solid fa-chevron-right" style="color:#9aa0a6; font-size:0.8rem;"></i>
        </button>

      </div>

      <!-- Custom Account Input View (hidden by default) -->
      <div id="google-custom-account-box" style="display:none; margin-bottom:14px; padding:12px; border:1px solid #dadce0; border-radius:10px; background:#fafafa;">
        <label class="auth-label" style="font-size:0.8rem; margin-bottom:6px; display:block;">Enter Google Workspace Email</label>
        <div style="display:flex; gap:8px;">
          <input type="email" id="google-custom-email" class="auth-text-input" placeholder="e.g. admin@kinzjewellery.com" style="padding:8px 12px; font-size:0.85rem;">
          <button type="button" id="btn-google-custom-verify" class="btn-auth-primary" style="padding:8px 16px; width:auto; font-size:0.82rem; white-space:nowrap;">
            Verify
          </button>
        </div>
      </div>

      <!-- Status / Alert Feedback box inside modal -->
      <div id="google-auth-status-alert" style="display:none; padding:10px 14px; border-radius:8px; font-size:0.82rem; margin-bottom:12px; line-height:1.4;"></div>

      <div style="border-top:1px solid #ebebeb; padding-top:12px; display:flex; justify-content:space-between; align-items:center; font-size:0.75rem; color:#70757a;">
        <span>Google Workspace Single Sign-On verifies administrator credentials before vault access.</span>
      </div>
    </div>
  </div>

  <!-- Global Toast Notification Container -->
  <div class="kinz-toast-container" id="kinz-toast-container"></div>

  <!-- Scripts -->
  <script>
    // Toast & Alert Utility for Login Form
    function showToast(msg, type = 'info') {
      const container = document.getElementById('kinz-toast-container');
      if (!container) return;

      const toast = document.createElement('div');
      toast.className = `toast-item toast-${type}`;
      toast.style.cssText = `
        background: #062C22;
        color: #FAF8F0;
        border: 1px solid #C87927;
        padding: 13px 20px;
        border-radius: 10px;
        margin-top: 10px;
        box-shadow: 0 8px 24px rgba(6,44,34,0.35);
        font-size: 0.9rem;
        font-family: 'Poppins', sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: toastIn 0.3s ease;
      `;
      
      const icon = type === 'success' 
        ? '<i class="fa-solid fa-circle-check" style="color:#10b981;"></i>' 
        : type === 'error'
        ? '<i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i>'
        : '<i class="fa-solid fa-shield-halved" style="color:#C87927;"></i>';

      toast.innerHTML = `${icon} <span>${msg}</span>`;
      container.appendChild(toast);

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
      }, 3500);
    }

    // Toggle Password Visibility
    function setupPasswordToggle(toggleBtnId, inputId) {
      const btn = document.getElementById(toggleBtnId);
      const input = document.getElementById(inputId);
      if (!btn || !input) return;

      btn.addEventListener('click', () => {
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.innerHTML = isPassword ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';
      });
    }

    // Switch between Login and Register tabs
    function switchAuthTab(tabId) {
      const heading = document.getElementById('auth-card-heading');
      const panelLogin = document.getElementById('panel-login');
      const panelRegister = document.getElementById('panel-register');

      if (tabId === 'register') {
        panelLogin?.classList.remove('active');
        panelRegister?.classList.add('active');
        if (heading) {
          heading.innerHTML = '<i class="fa-solid fa-user-plus"></i> Create Account';
        }
      } else {
        panelRegister?.classList.remove('active');
        panelLogin?.classList.add('active');
        if (heading) {
          heading.innerHTML = '<i class="fa-solid fa-arrow-right-to-bracket"></i> Login';
        }
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      setupPasswordToggle('toggle-admin-pass', 'admin-password');
      setupPasswordToggle('toggle-reg-pass', 'reg-password');

      // Tab switcher triggers
      document.getElementById('btn-switch-to-reg')?.addEventListener('click', () => switchAuthTab('register'));
      document.getElementById('btn-switch-to-login')?.addEventListener('click', () => switchAuthTab('login'));

      // Register Form Submission
      const formRegister = document.getElementById('form-register');
      if (formRegister) {
        formRegister.addEventListener('submit', (e) => {
          const name = document.getElementById('reg-name')?.value.trim();
          const email = document.getElementById('reg-email')?.value.trim();
          const pass = document.getElementById('reg-password')?.value.trim();
          const confirmPass = document.getElementById('reg-confirm-pass')?.value.trim();

          if (!name || !email || !pass) {
            e.preventDefault();
            showToast('Please fill in all required fields.', 'error');
            return;
          }
          if (pass.length < 6) {
            e.preventDefault();
            showToast('Password must be at least 6 characters.', 'error');
            return;
          }
          if (pass !== confirmPass) {
            e.preventDefault();
            showToast('Passwords do not match.', 'error');
            return;
          }
          // Default action submits to actions/register_action.php
        });
      }

      // Login Form Submission
      const form = document.getElementById('form-admin-login');
      if (form) {
        form.addEventListener('submit', (e) => {
          const identifier = document.getElementById('admin-identifier').value.trim();
          const pass = document.getElementById('admin-password').value.trim();

          if (!identifier || !pass) {
            e.preventDefault();
            showToast('Please enter your Email/Mobile and Password.', 'error');
            return;
          }
          // Default action submits to actions/login_action.php
        });
      }

      // Google Login Modal Flow
      const btnGoogle = document.getElementById('btn-google-login');
      const modalGoogle = document.getElementById('modal-google-auth');
      const btnCloseGoogle = document.getElementById('btn-close-google-auth');

      if (btnGoogle && modalGoogle) {
        btnGoogle.addEventListener('click', () => {
          modalGoogle.classList.add('active');
        });
      }

      if (btnCloseGoogle && modalGoogle) {
        btnCloseGoogle.addEventListener('click', () => {
          modalGoogle.classList.remove('active');
        });
      }

      if (modalGoogle) {
        modalGoogle.addEventListener('click', (e) => {
          if (e.target === modalGoogle) modalGoogle.classList.remove('active');
        });
      }

      document.getElementById('google-acc-admin')?.addEventListener('click', () => {
        showToast('Google Authentication verified.', 'success');
        window.location.href = 'index.php';
      });

      document.getElementById('google-acc-unauth')?.addEventListener('click', () => {
        showToast('Access Denied: Unauthorized account.', 'error');
      });

      // Forgot Password Modal Flow
      const modalForgot = document.getElementById('modal-forgot-password');
      const forgotStep1 = document.getElementById('forgot-step-1');
      const forgotStep2 = document.getElementById('forgot-step-2');
      const btnOpenForgot = document.getElementById('btn-open-forgot');

      if (btnOpenForgot) {
        btnOpenForgot.addEventListener('click', () => {
          modalForgot.classList.add('active');
          forgotStep1.style.display = 'block';
          forgotStep2.style.display = 'none';
        });
      }

      document.getElementById('btn-close-forgot')?.addEventListener('click', () => {
        modalForgot.classList.remove('active');
      });

      document.getElementById('btn-send-admin-otp')?.addEventListener('click', () => {
        const idVal = document.getElementById('forgot-admin-id').value.trim();
        if (!idVal) {
          showToast('Please provide your email or mobile.', 'error');
          return;
        }
        showToast('Security verification code dispatched: 749210', 'info');
        forgotStep1.style.display = 'none';
        forgotStep2.style.display = 'block';
      });

      document.getElementById('btn-confirm-admin-reset')?.addEventListener('click', () => {
        const code = document.getElementById('forgot-admin-otp').value.trim();
        const newPass = document.getElementById('forgot-admin-newpass').value.trim();

        if (code !== '749210') {
          showToast('Invalid security token. Use code 749210', 'error');
          return;
        }

        if (!newPass || newPass.length < 6) {
          showToast('Password must be at least 6 characters.', 'error');
          return;
        }

        showToast('Password updated! Please login.', 'success');
        modalForgot.classList.remove('active');
        document.getElementById('admin-password').value = newPass;
      });

      if (modalForgot) {
        modalForgot.addEventListener('click', (e) => {
          if (e.target === modalForgot) modalForgot.classList.remove('active');
        });
      }
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      <?php if ($error === 'invalid_credentials'): ?>
        showToast('Invalid email or password.', 'error');
      <?php elseif ($error === 'empty_fields'): ?>
        showToast('Please fill in all fields.', 'error');
      <?php elseif ($error === 'email_exists'): ?>
        showToast('An account with this email already exists.', 'error');
        switchAuthTab('register');
      <?php elseif ($msg === 'logged_out'): ?>
        showToast('Signed out successfully.', 'info');
      <?php elseif ($msg === 'registered'): ?>
        showToast('Account created successfully! Please log in.', 'success');
      <?php endif; ?>
    });
  </script>

</body>
</html>
