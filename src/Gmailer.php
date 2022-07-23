<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Gmailer {

    public function __construct(object $setup) {
        $this->username = $setup->username;
        $this->appPassword = $setup->appPassword;
        $this->port = $setup->port;
        $this->senderName = $setup->senderName;
        $this->subject = $setup->subject;
        $this->message = $setup->message;
        $this->target = $setup->target;
        $this->max = $setup->max;
        $this->skipBefore = $setup->skip;
        $this->files = explode(',', $setup->files);
        $this->debug = $setup->debug;
    }

    public function getUsername() : string {
        return $this->username;
    }

    public function getAppPassword() : string {
        return $this->appPassword;
    }

    public function getPort() : int {
        return $this->port;
    }

    public function getSenderName() : string {
        return $this->senderName;
    }

    public function getSubject() : string {
        return $this->subject;
    }

    public function getMessage() : string {
        return $this->message;
    }

    public function getTarget() : string {
        return $this->target;
    }

    public function getMax() : int {
        return $this->max;
    }

    public function getSkipBefore() : int {
        return $this->skipBefore;
    }

    public function getFiles() : array {
        return $this->files;
    }

    public function getDebug() : bool {
        return $this->debug ?? 0;
    }

    private function handler() {
        $handler = new PHPMailer();
        $handler->IsSMTP();

        $handler->Mailer = 'smtp';
        $handler->SMTPAuth   = TRUE;
        $handler->SMTPSecure = 'tls';
        $handler->Host = 'smtp.gmail.com';
        
        $handler->SMTPDebug = $this->getDebug() ?? 0;  
        $handler->IsHTML($this->html ?? true);

        $handler->Port = $this->getPort() ?? 587;
        $handler->Username = $this->getUsername();
        $handler->Password = $this->getAppPassword();
        
        $handler->SetFrom($this->getUsername(), $this->getSenderName());
        $handler->AddReplyTo($this->getUsername(), $this->getSenderName());
        
        $handler->Subject = $this->getSubject();
        $handler->MsgHTML($this->getMessage()); 
        
        return $handler;
    }

    public function send() {
        $emails = file($this->getTarget(), FILE_IGNORE_NEW_LINES);
        $startedAt = microtime(true);

        $sent = $skipped = $failed = 0;
        
        // $emails = ['saqibrzzaq@gmail.com'];
        for ($i=0; $i < $this->max; $i++) { 
            if ($i < $this->getSkipBefore()) { $skipped += 1; continue; }
            
            $currentEmail = $emails[$i];
            if (strlen($currentEmail) < 5 || !strstr($currentEmail, '@') || !strstr($currentEmail, '.')) {
                $skipped += 1;
                echo "Skipped malformed email address {$currentEmail}\n";
                continue;
            }
        
            $mail = $this->handler();
            $mail->AddAddress($currentEmail);
        
            if (!empty($this->getFiles())) {
                foreach ($this->getFiles() as $file) {
                    $mail->addAttachment($file); 
                }
            }
        
            $elapsed = round(round((microtime(true) - $startedAt), 3) / 60, 3);
            $message = "processed: {$i} / {$this->max}, ok: {$sent}, skipped: {$skipped}, failed: {$failed}, last: $currentEmail";

            if(!$mail->Send()) {
                $failed += 1;
                echo "error: {$mail->ErrorInfo}: {$message}\n";
            } else {
                $sent += 1;
                echo "sent: {$message}\n";
                // $setup->skip_before = $i;
                // file_put_contents($setupFile, json_encode($setup, JSON_PRETTY_PRINT));
            }
        }
    }
}