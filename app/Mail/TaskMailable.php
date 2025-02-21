<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $task;
    public $user;
    public $url;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($task, $user, $url)
    {
        $this->task = $task;
        $this->user = $user;
        $this->url = $url;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: '【' . $this->task->restaurant->brand . $this->task->restaurant->shop . '】' . $this->task->category . ' ' . $this->task->task_date
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }

    public function build()
    {
        return $this->view('emails')
            ->with([
                'shop' => $this->task->restaurant->brand . $this->task->restaurant->shop,
                'category' => $this->task->category,
                'task_date' => $this->task->task_date,
                'user_name' => $this->task->users->pluck('name')->implode('、'),
                'meal_name' => $this->task->meals->pluck('name')->implode('、'),
                'project_name' => $this->task->projects->pluck('description')->implode('、'),
                'url' => $this->url,
            ]);
    }
}
