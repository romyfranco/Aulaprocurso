<?php

namespace App\Notifications;

use App\Models\Quiz;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExtraAttemptGranted extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Quiz $quiz, public int $totalExtraAttempts, public ?string $reason = null)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tienes un intento extra disponible')
            ->greeting('Hola, '.$notifiable->name)
            ->line('Tu instructor habilitó un nuevo intento para '.$this->quiz->title.'.')
            ->when($this->reason, fn (MailMessage $mail) => $mail->line('Motivo: '.$this->reason))
            ->action('Ir a mis evaluaciones', url('/student'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['quiz_id' => $this->quiz->id, 'quiz_title' => $this->quiz->title, 'extra_attempts' => $this->totalExtraAttempts, 'reason' => $this->reason];
    }
}
