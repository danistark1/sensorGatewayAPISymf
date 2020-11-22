<?php


namespace App;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class WeatherStationEmail {

    /** @var string */
    public  $from;

    /** @var string */
    public  $to;

    /** @var string */
    public  $cc;

    /** @var string */
    public  $bcc;

    public  $template;

    public  $emailData;
    private $mailer;
    private $templating;

    /**
     * WeatherStationEmail constructor.
     *
     * @param $from
     * @param array $to
     * @param array $cc
     * @param array $bcc
     */
    public function __Construct(
        \Twig\Environment $templating,
        MailerInterface $mailer,
        $from,
        array $to,
        $template,
        array $emailData
    ) {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->from = $from;
        $this->to = $to;
        $this->prepareEmail();
}

    /**
     *
     */
    private function prepareEmail() {

    $email = (new Email())
        ->from($this->from)
        ->to($this->to)
        ->html(
            $this->templating->render(
                $this->template,
                ['stationData' => $this->emailData]
            ),
            'text/html'
        );
    $this->mailer->send($email);
    }
}
