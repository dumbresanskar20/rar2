<?php
/**
 * RAR Consultant - Mailer Configuration Template
 * 
 * Copy this file to "mailer-config.php" in the same directory (api/mailer-config.php)
 * and fill in your actual SMTP credentials.
 * 
 * "mailer-config.php" is git-ignored so your passwords will NOT be committed to git.
 */

// OPTION 1: cPanel / Webmail SMTP (Recommended for your domain)
// Set $smtp_enabled to true and enter your email account password below.
$smtp_enabled = false; // Change to true once password is set
$smtp_host    = 'mail.rarconsultant.co.in';
$smtp_port    = 465;                        // 465 (SSL) or 587 (TLS)
$smtp_secure  = 'ssl';                      // 'ssl' for 465, 'tls' for 587
$smtp_user    = 'anand@rarconsultant.co.in';
$smtp_pass    = '';                         // Enter email password here

/*
// OPTION 2: Gmail / Google Workspace SMTP (Alternative)
// Requires a 16-character "App Password" from your Google Account (Security -> 2-Step Verification -> App Passwords)
$smtp_enabled = true;
$smtp_host    = 'smtp.gmail.com';
$smtp_port    = 465;
$smtp_secure  = 'ssl';
$smtp_user    = 'youraccount@gmail.com'; // or Google Workspace email
$smtp_pass    = 'xxxx xxxx xxxx xxxx';   // 16-character Google App Password
*/

