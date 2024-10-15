<?php

namespace App\Notifications;

use App\Channels\TwilioChannel;
use App\Services\MessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class UserPasswordNotification extends Notification implements ShouldQueue {
  use Queueable;

  private $data;
  private $messagingService;
  public function __construct($data) {
    $this->data = $data;
    $this->messagingService = new MessagingService();
  }

  /**
   * Get the notification's delivery channels.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function via($notifiable) {
    return ['mail', TwilioChannel::class];
  }

  /**
  https://studysection.com/blog/custom-notification-in-laravel/
   */
  public function toMail($notifiable) {
    $name = $this->data["name"];
    $last_name = $this->data["last_name"];
    $password = $this->data["password"];
    return (new MailMessage)
      ->greeting("Hola $name $last_name ")
      ->line('Bienvenido al Sistema de la Fe!')
      ->line(new HtmlString("Tu contraseña de acceso es <strong>$password</strong>"))

      ->action('Iniciar Sesión', url('/login'));
  }

  public function toTwilio($notifiable) {
    $name = $this->data["name"];
    $last_name = $this->data["last_name"];
    $password = $this->data["password"];
    $cellphone = $this->data["cellphone"];
    $this->messagingService->sendSMS($cellphone,
      ['type' => MessagingService::NEW_USER,
        'name' => $name,
        'last_name' => $last_name,
        'password' => $password,
      ]);
  }

  /**
   * Get the array representation of the notification.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function toArray($notifiable) {
    return [
      'name' => $this->data['name'],
    ];
  }
}
