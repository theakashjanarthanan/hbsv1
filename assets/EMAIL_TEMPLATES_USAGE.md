# Email Templates Usage Guide

This document explains how to use the professional email templates available in `assets/email_template.php`.

## Available Email Templates

### 1. Forward Bookings Email
**Function:** `getForwardBookingEmail()`

**Usage:**
```php
include('assets/email_template.php');
$msg = getForwardBookingEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen);
smtp_mailer($user_email, 'Booking Forwarded', $msg);
```

**Parameters:**
- `$organiserName` - Name of the organizer
- `$hallName` - Name of the hall
- `$department` - Department name
- `$dateInfo` - Date information (e.g., "2024-01-15" or "from 2024-01-15 to 2024-01-20")
- `$sessionType` - Session type (e.g., "Forenoon", "Afternoon", "Full Day") or empty string
- `$slotTime` - Time slots (e.g., "9:30 AM to 12:30 PM")
- `$bookingIdGen` - Booking ID (e.g., "240115S001")

---

### 2. Password Recovery Email
**Function:** `getPasswordRecoveryEmail()`

**Usage:**
```php
include('assets/email_template.php');
$resetLink = "http://yourdomain.com/reset_password.php?token=" . urlencode($token);
$msg = getPasswordRecoveryEmail($resetLink);
$mail->Body = $msg;
```

**Parameters:**
- `$resetLink` - Complete URL for password reset with token

---

### 3. Password Changed Successfully Email
**Function:** `getPasswordChangedEmail()`

**Usage:**
```php
include('assets/email_template.php');
$msg = getPasswordChangedEmail($username);
smtp_mailer($user_email, 'Password Changed Successfully', $msg);
```

**Parameters:**
- `$username` - Username of the user

---

### 4. Booking Cancelled Email
**Function:** `getBookingCancelledEmail()`

**Usage:**
```php
include('assets/email_template.php');
$msg = getBookingCancelledEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $cancellationReason, $bookingIdGen);
smtp_mailer($user_email, 'Booking Cancelled', $msg);
```

**Parameters:**
- `$organiserName` - Name of the organizer
- `$hallName` - Name of the hall
- `$department` - Department name
- `$dateInfo` - Date information
- `$sessionType` - Session type or empty string
- `$slotTime` - Time slots
- `$cancellationReason` - Reason for cancellation
- `$bookingIdGen` - Booking ID

---

### 5. Booking Deleted Email
**Function:** `getBookingDeletedEmail()`

**Usage:**
```php
include('assets/email_template.php');
$msg = getBookingDeletedEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen);
smtp_mailer($user_email, 'Booking Deleted', $msg);
```

**Parameters:**
- `$organiserName` - Name of the organizer
- `$hallName` - Name of the hall
- `$department` - Department name
- `$dateInfo` - Date information
- `$sessionType` - Session type or empty string
- `$slotTime` - Time slots
- `$bookingIdGen` - Booking ID

---

### 6. Feedback Reminder Email (After 7 Days)
**Function:** `getFeedbackReminderEmail()`

**Usage:**
```php
include('assets/email_template.php');
$feedbackLink = "http://yourdomain.com/submit_feedback.php?booking_id=" . $booking_id;
$msg = getFeedbackReminderEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen, $feedbackLink);
smtp_mailer($user_email, 'Feedback Request', $msg);
```

**Parameters:**
- `$organiserName` - Name of the organizer
- `$hallName` - Name of the hall
- `$department` - Department name
- `$dateInfo` - Date information
- `$sessionType` - Session type or empty string
- `$slotTime` - Time slots
- `$bookingIdGen` - Booking ID
- `$feedbackLink` - Complete URL to feedback submission page

---

### 7. Thank You Feedback Submitted Email
**Function:** `getFeedbackThankYouEmail()`

**Usage:**
```php
include('assets/email_template.php');
$msg = getFeedbackThankYouEmail($organiserName, $hallName, $department);
smtp_mailer($user_email, 'Thank You for Your Feedback', $msg);
```

**Parameters:**
- `$organiserName` - Name of the organizer
- `$hallName` - Name of the hall
- `$department` - Department name

---

### 8. Booking Reminder Email (30 Minutes Before)
**Function:** `getBookingReminderEmail()`

**Usage:**
```php
include('assets/email_template.php');
$timeRemaining = "30 minutes"; // or "1 hour", etc.
$msg = getBookingReminderEmail($organiserName, $hallName, $department, $dateInfo, $sessionType, $slotTime, $bookingIdGen, $timeRemaining);
smtp_mailer($user_email, 'Booking Reminder - Starting Soon', $msg);
```

**Parameters:**
- `$organiserName` - Name of the organizer
- `$hallName` - Name of the hall
- `$department` - Department name
- `$dateInfo` - Date and time information
- `$sessionType` - Session type or empty string
- `$slotTime` - Time slots
- `$bookingIdGen` - Booking ID
- `$timeRemaining` - Time remaining until booking (e.g., "30 minutes", "1 hour")

---

## Common Helper Functions

### Date Format Helper
```php
// For single date
$dateInfo = $start_date;

// For date range
if ($start_date == $end_date) {
    $dateInfo = $start_date;
} else {
    $dateInfo = "from $start_date to $end_date";
}
```

### Session Type Helper
```php
$sessionType = "";
$slotTime = "";

if (count($slots) == 8) {
    $sessionType = "Full Day";
    $slotTime = "9:30 AM to 4:30 PM";
} elseif (count(array_intersect($slots, [1, 2, 3, 4])) == 4) {
    $sessionType = "Forenoon";
    $slotTime = "9:30 AM to 12:30 PM";
} elseif (count(array_intersect($slots, [5, 6, 7, 8])) == 4) {
    $sessionType = "Afternoon";
    $slotTime = "1:30 PM to 4:30 PM";
} else {
    // Custom slots
    $slotTime = implode(", ", array_map(function($slot) use ($slot_times) {
        return isset($slot_times[$slot]) ? $slot_times[$slot] : "Invalid Slot";
    }, $slots));
}
```

---

## Notes

- All email templates use professional HTML formatting with responsive design
- Templates are mobile-friendly and work across all email clients
- All user inputs are automatically sanitized using `htmlspecialchars()`
- Templates follow the HBS - Pondicherry University brand guidelines
- Always include `assets/email_template.php` before using any template function

---

## Example: Complete Implementation

```php
<?php
include('smtp/PHPMailerAutoload.php');
include('assets/conn.php');
include('assets/email_template.php');

// Fetch booking details
$booking_id = $_GET['booking_id'];
// ... fetch booking data from database ...

// Prepare date info
if ($start_date == $end_date) {
    $dateInfo = $start_date;
} else {
    $dateInfo = "from $start_date to $end_date";
}

// Use template
$msg = getBookingCancelledEmail(
    $organiser_name,
    $hall_name,
    $department_name,
    $dateInfo,
    $session_type,
    $slot_time,
    $cancellation_reason,
    $booking_id_gen
);

// Send email
smtp_mailer($user_email, 'Booking Cancelled', $msg);
?>
```

