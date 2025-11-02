<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Purchase;
use App\Models\User;

class TradeBuyerCompletedNotification extends Notification
{
    use Queueable;

    /** @var \App\Models\Purchase */
    protected $purchase;

    /** @var \App\Models\User */
    protected $buyer;

    /** @var \App\Models\User */
    protected $seller;

    /** @var int */
    protected $roomId;

    public function __construct(Purchase $purchase, User $buyer, User $seller, $roomId)
    {
        $this->purchase = $purchase;
        $this->buyer    = $buyer;
        $this->seller   = $seller;
        $this->roomId   = (int) $roomId;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $item  = $this->purchase->item;
        $title = $item ? $item->name : '取引商品';

        $mail = new MailMessage();
        $mail->subject('購入者による評価と取引が完了しました');
        $mail->greeting($this->seller->name . ' 様');
        $mail->line('以下の取引で、購入者による評価と取引が完了しました。');
        $mail->line('商品名：' . $title);
        $mail->line('購入者：' . $this->buyer->name);
        $mail->line('購入者の評価をお願いいたします。');
        $link = route('trade.show', ['roomId' => $this->roomId]);
        $mail->action('取引ページを開く', $link);

        return $mail;
    }
}
