<?php
require_once __DIR__ . '/env.php';

class EmailService {
    private $from_email;
    private $from_name;
    private $smtp_enabled;
    private $gmail_username;
    private $gmail_password;
    private $smtp_host;
    private $smtp_port;
    
    public function __construct() {
        // Load configuration from environment variables
        $this->from_email = EnvLoader::get('FROM_EMAIL', 'noreply@taskmanager.local');
        $this->from_name = EnvLoader::get('FROM_NAME', 'Task Manager System');
        $this->smtp_enabled = EnvLoader::getBool('SMTP_ENABLED', false);
        $this->gmail_username = EnvLoader::get('GMAIL_USERNAME', '');
        $this->gmail_password = EnvLoader::get('GMAIL_PASSWORD', '');
        $this->smtp_host = EnvLoader::get('SMTP_HOST', 'smtp.gmail.com');
        $this->smtp_port = EnvLoader::getInt('SMTP_PORT', 587);
    }
    
    /**
     * Send task assignment notification
     */
    public function sendTaskAssignment($user_email, $user_name, $task_title, $task_description, $deadline, $created_by) {
        $subject = "New Task Assigned: " . $task_title;
        
        $html_message = $this->getTaskAssignmentHTML($user_name, $task_title, $task_description, $deadline, $created_by);
        $text_message = $this->getTaskAssignmentText($user_name, $task_title, $task_description, $deadline, $created_by);
        
        return $this->sendEmail($user_email, $user_name, $subject, $html_message, $text_message);
    }
    
    /**
     * Send email using PHP mail() function
     */
    private function sendEmail($to_email, $to_name, $subject, $html_message, $text_message) {
        if ($this->smtp_enabled) {
            // Use SMTP for Gmail
            return $this->sendGmailSMTP($to_email, $to_name, $subject, $html_message);
        } else {
            // Use MailHog for development or basic mail() for production
            if (EnvLoader::get('APP_ENV') === 'development') {
                return $this->sendMailHog($to_email, $to_name, $subject, $html_message, $text_message);
            } else {
                return $this->sendBasicEmail($to_email, $to_name, $subject, $html_message, $text_message);
            }
        }
    }
    
    /**
     * Send email using MailHog for development
     */
    private function sendMailHog($to_email, $to_name, $subject, $html_message, $text_message) {
        // Configure PHP to use MailHog
        ini_set('SMTP', 'localhost');
        ini_set('smtp_port', '1025');
        ini_set('sendmail_from', 'noreply@taskmanager.local');
        
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        $headers_string = implode("\r\n", $headers);
        
        try {
            $result = mail($to_email, $subject, $html_message, $headers_string);
            
            if ($result) {
                error_log("Email sent successfully to: " . $to_email);
                return true;
            } else {
                error_log("Failed to send email to: " . $to_email);
                return false;
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send email using basic PHP mail() function
     */
    private function sendBasicEmail($to_email, $to_name, $subject, $html_message, $text_message) {
        $headers = array();
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: {$this->from_name} <{$this->from_email}>";
        $headers[] = "Reply-To: {$this->from_email}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        $headers_string = implode("\r\n", $headers);
        
        try {
            $result = mail($to_email, $subject, $html_message, $headers_string);
            
            if ($result) {
                error_log("Email sent successfully to: " . $to_email);
                return true;
            } else {
                error_log("Failed to send email to: " . $to_email);
                return false;
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate HTML email template for task assignment
     */
    private function getTaskAssignmentHTML($user_name, $task_title, $task_description, $deadline, $created_by) {
        $deadline_text = $deadline ? date('F j, Y', strtotime($deadline)) : 'No deadline set';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { background: #f9f9f9; padding: 20px; }
                .task-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .deadline { color: #dc3545; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>New Task Assigned</h2>
                </div>
                
                <div class='content'>
                    <p>Hello <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                    
                    <p>You have been assigned a new task by <strong>" . htmlspecialchars($created_by) . "</strong>.</p>
                    
                    <div class='task-details'>
                        <h3>" . htmlspecialchars($task_title) . "</h3>
                        
                        " . ($task_description ? "<p><strong>Description:</strong><br>" . nl2br(htmlspecialchars($task_description)) . "</p>" : "") . "
                        
                        <p><strong>Deadline:</strong> <span class='deadline'>" . $deadline_text . "</span></p>
                        
                        <p><strong>Status:</strong> Pending</p>
                    </div>
                    
                    <p>Please log in to the Task Manager system to view and manage your tasks.</p>
                    
                    <p style='text-align: center;'>
                        <a href='" . BASE_URL . "' class='btn'>View My Tasks</a>
                    </p>
                </div>
                
                <div class='footer'>
                    <p>This is an automated message from the Task Manager System.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate plain text email for task assignment
     */
    private function getTaskAssignmentText($user_name, $task_title, $task_description, $deadline, $created_by) {
        $deadline_text = $deadline ? date('F j, Y', strtotime($deadline)) : 'No deadline set';
        
        $message = "NEW TASK ASSIGNED\n\n";
        $message .= "Hello " . $user_name . ",\n\n";
        $message .= "You have been assigned a new task by " . $created_by . ".\n\n";
        $message .= "TASK DETAILS:\n";
        $message .= "Title: " . $task_title . "\n";
        
        if ($task_description) {
            $message .= "Description: " . $task_description . "\n";
        }
        
        $message .= "Deadline: " . $deadline_text . "\n";
        $message .= "Status: Pending\n\n";
        $message .= "Please log in to the Task Manager system to view and manage your tasks.\n";
        $message .= "Link: " . BASE_URL . "\n\n";
        $message .= "This is an automated message from the Task Manager System.\n";
        $message .= "Please do not reply to this email.";
        
        return $message;
    }
    
    /**
     * Send email using Gmail SMTP
     */
    private function sendGmailSMTP($to_email, $to_name, $subject, $html_message) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->gmail_username}>" . "\r\n";
        $headers .= "Reply-To: {$this->gmail_username}" . "\r\n";
        
        // Configure PHP to use Gmail SMTP
        ini_set('SMTP', $this->smtp_host);
        ini_set('smtp_port', $this->smtp_port);
        ini_set('sendmail_from', $this->gmail_username);
        
        try {
            $result = mail($to_email, $subject, $html_message, $headers);
            
            if ($result) {
                error_log("Gmail SMTP: Email sent successfully to: " . $to_email);
                return true;
            } else {
                error_log("Gmail SMTP: Failed to send email to: " . $to_email);
                return false;
            }
        } catch (Exception $e) {
            error_log("Gmail SMTP error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test email configuration
     */
    public function testEmail($test_email) {
        $subject = "Task Manager - Email Test";
        $message = "This is a test email from the Task Manager system. If you receive this, email notifications are working correctly.";
        
        if ($this->smtp_enabled) {
            return $this->sendGmailSMTP($test_email, '', $subject, $message);
        } else {
            $headers = array();
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
            $headers[] = "From: {$this->from_name} <{$this->from_email}>";
            
            return mail($test_email, $subject, $message, implode("\r\n", $headers));
        }
    }
}
?>
