<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Purchase;
use App\Models\User;

class TradeBuyerCompletedNotification extends Notification
{
    protected Purchase $purchase;
    protected User $buyer;
    protected User $seller;
    protected int $roomId;

    public function __construct(Purchase $purchase, User $buyer, User $seller, int $roomId)
    {
        $this->purchase = $purchase;
        $this->buyer    = $buyer;
        $this->seller   = $seller;
        $this->roomId   = $roomId;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $item  = $this->purchase->item;
        $title = $item ? $item->name : '取引商品';

        return (new MailMessage)
            ->subject('購入者による評価と取引が完了しました')
            ->greeting($this->seller->name . ' 様')
            ->line('以下の取引で、購入者による評価と取引が完了しました。')
            ->line('商品名：' . $title)
            ->line('購入者：' . $this->buyer->name)
            ->line('購入者の評価をお願いいたします。')
            ->action('取引ページを開く', route('trade.show', ['roomId' => $this->roomId]));
    }
}
