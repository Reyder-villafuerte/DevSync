<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MilkFlowNotification extends Notification
{
    public function __construct(public string $title, public string $message, public bool $email = false) {}

    public function via(object $notifiable): array
    {
        return $this->email ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['titulo' => $this->title, 'mensaje' => $this->message];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->title)->greeting('Hola, '.$notifiable->name)->line($this->message)->action('Abrir MilkFlow', url('/login'));
    }
}
