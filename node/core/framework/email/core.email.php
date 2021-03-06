<?php
/*
    PufferPanel - A Minecraft Server Management Panel
    Copyright (c) 2013 Dane Everitt
 
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see http://www.gnu.org/licenses/.
 */
 
/*
 * Core Email Sending Class
 */

class tplMail extends dbConn {

    private $message;
    
	public function __construct($settings)
		{
	
			$this->mysql = parent::getConnection();
			$this->settings = $settings;
	
		}
		
	/*
	 * Email Sending
	 */
	public function dispatch($email, $subject)
		{
		
			$this->getDispatchSystem = $this->getDispatchSystemFunct();
			if($this->getDispatchSystem == 'php')
				{
				
					$headers = 'From: '. $this->settings->get('sendmail_email') . "\r\n" .
					    'Reply-To: '. $this->settings->get('sendmail_email') . "\r\n" .
					    'X-Mailer: PHP/' . phpversion();
	
					mail($email, $subject, $message, $headers);

				}
			else if($this->getDispatchSystem == 'postmark')
				{
				
					include('postmark/Mail.php');
					Postmark\Mail::compose($this->settings->get('postmark_api_key'))
					    ->from($this->settings->get('sendmail_email'), $this->settings->get('company_name'))
					    ->addTo($email, $email)
					    ->subject($subject)
					    ->messageHtml($this->message)
					    ->send();
				
				}
			else if($this->getDispatchSystem == 'mandrill')
				{
				
					include('mandrill/Mandrill.php');
					
					try {
					
					    $mandrill = new Mandrill($this->settings->get('mandrill_api_key'));
					    $mandrillMessage = array(
					        'html' => $this->message,
					        'subject' => $subject,
					        'from_email' => $this->settings->get('sendmail_email'),
					        'from_name' => $this->settings->get('company_name'),
					        'to' => array(
					            array(
					                'email' => $email,
					                'name' => $email
					            )
					        ),
					        'headers' => array('Reply-To' => $this->settings->get('sendmail_email')),
					        'important' => false
					    );
					    $async = true;
					    $ip_pool = 'Main Pool';
					    $result = $mandrill->messages->send($mandrillMessage, $async, $ip_pool);
					
					} catch(Mandrill_Error $e) {

					    echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
					    throw $e;
					
					}
									
				}
			else if($this->getDispatchSystem == 'mailgun')
				{
			
					list($name, $domain) = explode('@', $this->settings->get('sendmail_email'));
					
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, 'https://api.mailgun.net/v2/'.$domain.'/messages');
					curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
					curl_setopt($ch, CURLOPT_USERPWD, 'api:'.$this->settings->get('mailgun_api_key'));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
					curl_setopt($ch, CURLOPT_POSTFIELDS, array(
						'from' => $this->settings->get('company_name').' <'.$this->settings->get('sendmail_email').'>',
						'to' => $email.' <'.$email.'>',
						'subject' => $subject,
						'html' => $this->message
					));
					
					curl_exec($ch);
									
					curl_close($ch);
					
				}
			else 
				{
			
					$headers = 'From: '. $this->settings->get('sendmail_email') . "\r\n" .
					    'Reply-To: '. $this->settings->get('sendmail_email') . "\r\n" .
					    'X-Mailer: PHP/' . phpversion();
					
					mail($email, $subject, $this->message, $headers);
			
				}
		
		}
		
	private function getDispatchSystemFunct()
		{
		
			$this->selectSystem = $this->mysql->prepare("SELECT * FROM `acp_settings` WHERE `setting_ref` = 'sendmail_method'");
			$this->selectSystem->execute();
			
				$this->selectRow = $this->selectSystem->fetch();
				
				return $this->selectRow['setting_val'];
		
		}
	
	/*
	 * Email Creation & Templates
	 */

	private function readTemplate($template)
		{
		
			$this->tpl = $this->mysql->prepare("SELECT * FROM `acp_email_templates` WHERE `tpl_name` = ?");
			$this->tpl->execute(array($template));
			
			if($this->tpl->rowCount() == 1)
				{
				
					$row = $this->tpl->fetch();
					return $row['tpl_content'];
				
				}
			else 
				{
				
					die('Requested template `'.$template.'` could not be found.');
					
				}
			
		
		}
	
    /*
     * Read email template and compile data to send
     */
    public function buildEmail($tpl, $data = array())
        {
        
            $this->message = $this->readTemplate($tpl);
            $this->message = str_replace(array('<%HOST_NAME%>', '<%MASTER_URL%>', '<%DATE%>'), array($this->settings->get('company_name'), $this->settings->get('master_url'), date('j/F/Y H:i', time())), $this->message);
            
                foreach($data as $key => $val)
                    $this->message  = str_replace('<%'.$key.'%>', $val, $this->message);
        
                return $this;
                
        }

}

?>