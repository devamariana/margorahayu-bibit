<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SistemNotifikasi extends Notification
{
    use Queueable;

    public $judul;
    public $pesan;
    public $tipe;
    public $url;
    public $id_terkait;

    /**
     * Create a new notification instance.
     */
    public function __construct($judul, $pesan, $tipe = 'info', $url = null, $id_terkait = null)
    {
        $this->judul = $judul;
        $this->pesan = $pesan;
        $this->tipe = $tipe;
        $this->url = $url;
        $this->id_terkait = $id_terkait;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'judul'      => $this->judul,
            'pesan'      => $this->pesan,
            'tipe'       => $this->tipe,
            'url'        => $this->url,
            'id_terkait' => $this->id_terkait,
        ];
    }
}
