<?php

namespace Application\Helper;

use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
// use Zend\Mail\Protocol\Smtp\Auth\Login;

class EmailHelper
{

    const maxMassMail = 50;
    const massEmailId = 'mhrpravin@gmail.com';

    public static function getSmtpTransport(): Smtp
    {
        $transport = new Smtp();
        $options = new SmtpOptions([
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'connection_class' => 'login',
            'connection_config' => [
                'username' => 'no-reply@ictcgroups.com',
                // 'password' => 'pleaseshareitwithRakeshdai123!@#',
                'username' => 'sabitamgr133@gmail.com',
                'password' => 'gxlgwfjehrbpdpsm',
                'ssl' => 'tls',
            ],
        ]);
        $transport->setOptions($options);
        return $transport;
    }

    public static function sendEmail(Message $mail)
    {
        if ('development' == APPLICATION_ENV || 'staging' == APPLICATION_ENV) {
            return true;
        }
        $transport = self::getSmtpTransport();
        $connectionConfig = $transport->getOptions()->getConnectionConfig();
        $mail->setFrom($connectionConfig['username']);
        //print_r($connectionConfig);die;
        $transport->send($mail);
        return true;
    }
}
