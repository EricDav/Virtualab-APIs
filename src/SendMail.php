<?php
    namespace VirtualLab;
    
    use VirtualLab\PHPMailer\PHPMailer;
    use VirtualLab\PHPMailer\SMTP;
    use VirtualLab\PHPMailer\Exception;


    class SendMail {
        
        public function __construct($to, $subject, $message, $isHeaderFooterSet=false) {
            $this->envObj = json_decode(file_get_contents(__DIR__ .'/.envJson'));
            $this->isHeaderFooterSet = $isHeaderFooterSet;
            $this->to = $to;
            $this->subject = $subject;
            $this->message = '';
            $this->mail = new PHPMailer(true);
            // $this->setMessage($message);
            $this->mes($message);
        }

        public function getEmailHeader() {
            $emailHeader = '<html><body style="font-size: 0.9rem;"><center>
                    <div style="width: 100%; background-color: #f5f6f7; padding-top: 50px; padding-bottom: 50px;">';
            $emailHeader .= '<div style="width: 80%; border-width: 1px;  border-style: solid; height: fit-content; background-color: #fff;">';

            $emailHeader .= '<div style="width: 100%;border-top: 0px;border-left: 0px;border-bottom: 1px;border-right: 0px;border-style: solid;height: 50px; background-color: rgb(35, 31, 32)">

                                <center><img width =60%; height=40;" src="https://mavinhub.com/MavinHub-logo.png" alt="logo"></center>
                            </div>';

            return $emailHeader;
        }

        public function getEmailFooter() {
            $emailFooter = "";
            $emailFooter .='<div style="text-align: center; margin-top: 50px; height: 50px; margin-bottom: -5;">
                                <center>
                                    <div style="color: #27aae1;font-weight: 700;">Contact Us</div>
                                    <span style="color: black; font-weight: 700;">+234 70 69229546</span> 
                                    <span style="color: black;font-weight: 700;margin-left: 10px;">info@mavinhub.com</span>
                                    <div style="color: black; font-weight: 600; font-style: oblique;">Visit our <a href="http://www.mavinhub.com">website</a> to view our products</div>
                                </center>
                            </div>';


            $emailFooter .='<div style="background: #231F20;text-align: center;color: #fff;top: 457;width: 100%; height: 45px; border-bottom: 0px;">
                                <center>
                                    <p style="padding-top: 10px;">mavinhub.com © 2021. All rights reserved</p>
                                </center>
                            </div>';
            $emailFooter .='</div>';
            $emailFooter .='</div></center><html><body>';
            
            return $emailFooter;
        }

        public function setMessage($message) {
            if (!$this->isHeaderFooterSet) {
                $this->message = $this->getEmailHeader() . $message .  $this->getEmailFooter();
            } else {
                $this->message = $message;
            }
        }

        public function mes($message) {
            $this->message = '<head>
            <meta name = "viewport" content = "width=device-width, initial-scale=1">
            <style>
                @media screen and (max-width: 420px){
                    .container{
                        width: 100% !important;
                    }
                }
                
                @media screen and (min-width: 420px){
                    .container{
                        width: 420px !important;
                    }
                }
                @media screen and (max-width: 352px){
                    .logo{
                        width: 80%;
                        height: auto
                    }
                }
                tr:nth-child(even){
                    background: #f2f2f2;
                }
            </style>
        </head>
        <!-- <meta http-equiv = "refresh" content = "1"> -->
            <div class = "container" style="width: 800px; height: auto; display: flex; justify-content: center;">
                <div style="width: 100%; border-width: 1px; border-style: solid; height: fit-content;">
                    
                    <center>
                        <div style="width: 100%;border-top: 0px;border-left: 0px;border-bottom: 1px;border-right: 0px;border-style: solid;height: 100px;">
                        <img  width="100" height="100" src="https://mavinhub.com/MavinHub-logo.png" alt="logo">
                        </div>
                    </center>' . $message .
                    
                    '<center>
                    <div style="font-size: 12px; flex-direction: column; margin-top: 100px; height: 50px;">
                        <div style="color: #27aae1;font-weight:­ 700;">
                            Contact Us
                        </div>
                        <div style="display: flex; justify-content: center">
                            <div style="color: black; font-weight: 700; width: 50%; text-align: right;">
                            +234 706 922 9546
                            </div>
                            <div style="color: black;font-weight: 700;margin-left: 10px; width: 50%; text-align: left;">
                                info@mavinub.com
                            </div>
                        </div>
                    </div>
                    </center>
                    <div style="font-size: 12px; background: #231F20;text-align: center;color: #fff;top: 457;width: 100%; height: 45px; display: flex; justify-content: center;">
                        <p style="text-align: center; width: 100%;"><b>Mavinhub</b> © 2021. All rights reserved</p>
                    </div>
                    <!-- end of footer content-->
            </div>
            </div>';
        }

        public function send() {
            try {
                // $this->mes();
                // var_dump($this->envObj->password); exit;
                $this->mail->isHTML(true); 
                $this->mail->isSMTP();
                $this->mail->Host  = "mavinhub.com";
                $this->mail->SMTPAuth   = true;                                 // Enable SMTP authentication
                $this->mail->Username   = $this->envObj->email;            // SMTP username
                $this->mail->Password   = $this->envObj->password;                      // SMTP password
                // $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;          // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $this->mail->Port       = 25;
                $this->mail->SMTPSecure = 'tsl';
                $this->mail->setFrom('no-reply@mavinhub.com');
                if (is_array($this->to)) {
                    foreach($this->to as $email) {
                        $this->mail->addAddress($email); 
                    }
                } else {
                    $this->mail->addAddress($this->to); 
                }                    
                $this->mail->Subject = $this->subject;
                $this->mail->Body    = $this->message;
                $this->mail->send();
                return true;
            } catch (Exception $e) {
                echo $e->getMessage();
                echo "Error while sending mail"; exit;
                mail("alienyidavid4christ@gmail.com","Mail Failure","Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
                // echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
                return false;
            }
        }
    }
?>
