<?php
/**
 * Professional Email Template Function
 * Generates professional HTML email templates for HBS - Pondicherry University
 */

function getEmailTemplate($title, $content, $footerText = null) {
    $footer = $footerText ?: "HBS - Pondicherry University<br>Hall Booking System";
    
    return '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background-color: #f4f4f4;">
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f4f4f4;">
            <tr>
                <td style="padding: 40px 20px;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <!-- Header -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #0e00a3 0%, #1a1a8e 100%); padding: 30px 40px; border-radius: 8px 8px 0 0;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td>
                                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 600; letter-spacing: 0.5px;">
                                                HBS - Pondicherry University
                                            </h1>
                                            <p style="margin: 8px 0 0 0; color: #e0e0e0; font-size: 14px;">
                                                Hall Booking System
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style="padding: 40px;">
                                ' . $content . '
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #f8f9fa; padding: 30px 40px; border-radius: 0 0 8px 8px; border-top: 1px solid #e9ecef;">
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="text-align: center;">
                                            <p style="margin: 0 0 10px 0; color: #6c757d; font-size: 14px; line-height: 1.6;">
                                                ' . $footer . '
                                            </p>
                                            <p style="margin: 0; color: #adb5bd; font-size: 12px;">
                                                This is an automated email. Please do not reply to this message.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}

function getBookingStatusEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $status, $statusColor, $purpose = '', $purposeName = '') {
    $statusDisplay = ucfirst($status);
    $statusBgColor = $statusColor === 'green' ? '#28a745' : ($statusColor === 'red' ? '#dc3545' : '#6c757d');
    
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Session:</strong>
                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                    </td>
                                </tr>';
    }
    
    // Purpose information
    $purposeInfo = '';
    if ($purpose || $purposeName) {
        $purposeDisplay = '';
        if ($purpose) {
            $purposeDisplay = '<span style="color: #0e00a3; font-weight: 600;">' . htmlspecialchars(ucfirst($purpose)) . '</span>';
        }
        if ($purposeName) {
            if ($purposeDisplay) {
                $purposeDisplay .= '<br>';
            }
            $purposeDisplay .= htmlspecialchars($purposeName);
        }
        
        $purposeInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Purpose:</strong>
                                        <div style="color: #212529; margin-left: 8px; margin-top: 4px;">
                                            ' . $purposeDisplay . '
                                        </div>
                                    </td>
                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #0e00a3; font-size: 22px; font-weight: 600;">
                                    Booking Status Update
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your booking request for the hall <strong>' . htmlspecialchars($hallName) . '</strong> in <strong>' . htmlspecialchars($department) . '</strong> has been updated. Please find the details below:
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall Name:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Department:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>
                                                ' . $purposeInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0 8px 0;">
                                                        <strong style="color: #495057;">Status:</strong>
                                                        <span style="display: inline-block; background-color: ' . $statusBgColor . '; color: #ffffff; padding: 4px 12px; border-radius: 4px; font-weight: 600; margin-left: 8px; font-size: 14px;">
                                                            ' . htmlspecialchars($statusDisplay) . '
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Thank you for using our Hall Booking System!
                                </p>';
    
    return getEmailTemplate('Booking Status Update', $content);
}

function getBookingConfirmationEmail($username, $dateInfo, $status, $sessionType, $slotTime, $hallName = '', $department = '') {
    $statusBgColor = '#28a745';
    $statusDisplay = ucfirst($status);
    
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Session:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                                    </td>
                                                </tr>';
    }
    
    $hallInfo = '';
    if ($hallName && $department) {
        $hallInfo = '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . ' - ' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #28a745; font-size: 22px; font-weight: 600;">
                                    ✓ Booking Confirmed
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($username) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your booking has been successfully submitted! We have received your request and it is currently being processed. Below are the booking details:
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8f9fa; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                ' . $hallInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0 8px 0;">
                                                        <strong style="color: #495057;">Status:</strong>
                                                        <span style="display: inline-block; background-color: ' . $statusBgColor . '; color: #ffffff; padding: 4px 12px; border-radius: 4px; font-weight: 600; margin-left: 8px; font-size: 14px;">
                                                            ' . htmlspecialchars($statusDisplay) . '
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    You will receive a notification once your booking is reviewed and approved. Thank you for using our Hall Booking System!
                                </p>';
    
    return getEmailTemplate('Booking Confirmation', $content);
}

function getPasswordRecoveryEmail($resetLink) {
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #0e00a3; font-size: 22px; font-weight: 600;">
                                    Password Reset Request
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear User,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    We received a request to reset your password for your HBS account. If you made this request, please click the button below to reset your password:
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 30px 0;">
                                    <tr>
                                        <td style="text-align: center;">
                                            <a href="' . htmlspecialchars($resetLink) . '" style="display: inline-block; background-color: #0e00a3; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                                Reset Password
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #6c757d; font-size: 14px; line-height: 1.6;">
                                    <strong>Note:</strong> If you did not request a password reset, please ignore this email. This link will expire in 24 hours for security reasons.
                                </p>
                                
                                <p style="margin: 15px 0 0 0; color: #6c757d; font-size: 13px; line-height: 1.6;">
                                    If the button above doesn\'t work, copy and paste the following link into your browser:
                                </p>
                                <p style="margin: 8px 0 0 0; color: #0e00a3; font-size: 13px; word-break: break-all;">
                                    ' . htmlspecialchars($resetLink) . '
                                </p>';
    
    return getEmailTemplate('Password Reset Request', $content);
}

function getForwardBookingEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen) {
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Session:</strong>
                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                    </td>
                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #ffc107; font-size: 22px; font-weight: 600;">
                                    📤 Booking Forwarded for Approval
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your booking request has been forwarded to the approving authority for review. Your booking details are as follows:
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #fff3cd; border-left: 4px solid #ffc107; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Booking ID:</strong>
                                                        <span style="color: #0e00a3; margin-left: 8px; font-weight: 600;">' . htmlspecialchars($bookingIdGen) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall Name:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Department:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0 8px 0;">
                                                        <strong style="color: #495057;">Status:</strong>
                                                        <span style="display: inline-block; background-color: #ffc107; color: #000000; padding: 4px 12px; border-radius: 4px; font-weight: 600; margin-left: 8px; font-size: 14px;">
                                                            Forwarded - Pending Approval
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your booking is now under review. You will be notified once a decision has been made. Thank you for your patience!
                                </p>';
    
    return getEmailTemplate('Booking Forwarded', $content);
}

function getPasswordChangedEmail($username) {
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #28a745; font-size: 22px; font-weight: 600;">
                                    ✓ Password Changed Successfully
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($username) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your password has been successfully changed. Your account is now secured with your new password.
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #d4edda; border-left: 4px solid #28a745; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <p style="margin: 0; color: #155724; font-size: 15px; line-height: 1.6;">
                                                <strong>✓ Security Confirmation:</strong><br>
                                                Your password change was completed successfully at ' . date('Y-m-d H:i:s') . '
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    If you did not make this change, please contact our support team immediately to secure your account.
                                </p>
                                
                                <p style="margin: 15px 0 0 0; color: #6c757d; font-size: 14px; line-height: 1.6;">
                                    <strong>Security Tip:</strong> Always use a strong, unique password and never share it with anyone.
                                </p>';
    
    return getEmailTemplate('Password Changed Successfully', $content);
}

function getBookingCancelledEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $cancellationReason, $bookingIdGen) {
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Session:</strong>
                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                    </td>
                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #dc3545; font-size: 22px; font-weight: 600;">
                                    ⚠️ Booking Cancelled
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your booking has been cancelled. Please find the details below:
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f8d7da; border-left: 4px solid #dc3545; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Booking ID:</strong>
                                                        <span style="color: #0e00a3; margin-left: 8px; font-weight: 600;">' . htmlspecialchars($bookingIdGen) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall Name:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Department:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0 8px 0;">
                                                        <strong style="color: #495057;">Cancellation Reason:</strong>
                                                        <span style="color: #721c24; margin-left: 8px;">' . htmlspecialchars($cancellationReason) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0 8px 0;">
                                                        <strong style="color: #495057;">Status:</strong>
                                                        <span style="display: inline-block; background-color: #dc3545; color: #ffffff; padding: 4px 12px; border-radius: 4px; font-weight: 600; margin-left: 8px; font-size: 14px;">
                                                            Cancelled
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    If you have any questions or need to reschedule, please contact the administration.
                                </p>';
    
    return getEmailTemplate('Booking Cancelled', $content);
}

function getBookingDeletedEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen) {
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Session:</strong>
                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                    </td>
                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #6c757d; font-size: 22px; font-weight: 600;">
                                    Booking Deleted
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your booking has been successfully deleted. It has been permanently removed from our system. The details of the deleted booking are as follows:
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #e9ecef; border-left: 4px solid #6c757d; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Booking ID:</strong>
                                                        <span style="color: #0e00a3; margin-left: 8px; font-weight: 600;">' . htmlspecialchars($bookingIdGen) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall Name:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Department:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    This booking has been permanently removed from the system. If you need to make a new booking, please visit the booking portal.
                                </p>';
    
    return getEmailTemplate('Booking Deleted', $content);
}

function getFeedbackReminderEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen, $feedbackLink) {
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Session:</strong>
                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                    </td>
                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #17a2b8; font-size: 22px; font-weight: 600;">
                                    📝 Feedback Request
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    We hope your recent booking went well! It\'s been 7 days since you used the hall, and we would love to hear about your experience. Your feedback helps us improve our services.
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #d1ecf1; border-left: 4px solid #17a2b8; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Booking ID:</strong>
                                                        <span style="color: #0e00a3; margin-left: 8px; font-weight: 600;">' . htmlspecialchars($bookingIdGen) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall Name:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Department:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date Used:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin: 30px 0;">
                                    <tr>
                                        <td style="text-align: center;">
                                            <a href="' . htmlspecialchars($feedbackLink) . '" style="display: inline-block; background-color: #17a2b8; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: 600; font-size: 16px;">
                                                Submit Feedback
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Your feedback is valuable to us and helps us maintain and improve our facilities. Thank you for taking the time to share your experience!
                                </p>';
    
    return getEmailTemplate('Feedback Request', $content);
}

function getFeedbackThankYouEmail($organiserName, $hallName, $department) {
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #28a745; font-size: 22px; font-weight: 600;">
                                    ✓ Thank You for Your Feedback!
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    Thank you for taking the time to submit your feedback regarding your experience at <strong>' . htmlspecialchars($hallName) . '</strong> in <strong>' . htmlspecialchars($department) . '</strong>.
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #d4edda; border-left: 4px solid #28a745; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <p style="margin: 0; color: #155724; font-size: 15px; line-height: 1.6; text-align: center;">
                                                <strong>✓ Feedback Received Successfully</strong><br>
                                                Your valuable feedback has been recorded and will help us improve our services.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    We appreciate your input and will use it to enhance the quality of our facilities and services. Your opinion matters to us!
                                </p>
                                
                                <p style="margin: 15px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    We look forward to serving you again in the future.
                                </p>';
    
    return getEmailTemplate('Thank You for Your Feedback', $content);
}

function getBookingReminderEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen, $timeRemaining) {
    $sessionInfo = '';
    if ($sessionType) {
        $sessionInfo = '
                                <tr>
                                    <td style="padding: 8px 0;">
                                        <strong style="color: #495057;">Session:</strong>
                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($sessionType) . '</span>
                                    </td>
                                </tr>';
    }
    
    $content = '
                                <h2 style="margin: 0 0 20px 0; color: #0e00a3; font-size: 22px; font-weight: 600;">
                                    ⏰ Booking Reminder
                                </h2>
                                
                                <p style="margin: 0 0 20px 0; color: #495057; font-size: 16px; line-height: 1.6;">
                                    Dear <strong>' . htmlspecialchars($organiserName) . '</strong>,
                                </p>
                                
                                <p style="margin: 0 0 25px 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    This is a friendly reminder that your booking is scheduled to start in <strong>' . htmlspecialchars($timeRemaining) . '</strong>. Please ensure you arrive on time.
                                </p>
                                
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #e7f3ff; border-left: 4px solid #0e00a3; border-radius: 6px; padding: 20px; margin: 25px 0;">
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Booking ID:</strong>
                                                        <span style="color: #0e00a3; margin-left: 8px; font-weight: 600;">' . htmlspecialchars($bookingIdGen) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Hall Name:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($hallName) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Department:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($department) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Date & Time:</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . $dateInfo . '</span>
                                                    </td>
                                                </tr>
                                                ' . $sessionInfo . '
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #495057;">Slot(s):</strong>
                                                        <span style="color: #212529; margin-left: 8px;">' . htmlspecialchars($slotTime) . '</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    <strong>Important Reminders:</strong>
                                </p>
                                <ul style="margin: 15px 0 0 0; padding-left: 20px; color: #495057; font-size: 15px; line-height: 1.8;">
                                    <li>Please arrive at least 10 minutes before your scheduled time</li>
                                    <li>Bring your booking confirmation (Booking ID: ' . htmlspecialchars($bookingIdGen) . ')</li>
                                    <li>Contact the hall in-charge if you need any assistance</li>
                                </ul>
                                
                                <p style="margin: 25px 0 0 0; color: #495057; font-size: 15px; line-height: 1.6;">
                                    We look forward to hosting your event. If you need to cancel or modify your booking, please do so as soon as possible.
                                </p>';
    
    return getEmailTemplate('Booking Reminder', $content);
}
?>

