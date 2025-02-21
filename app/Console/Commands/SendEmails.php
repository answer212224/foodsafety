<?php

namespace App\Console\Commands;

use App\Mail\TaskMailable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '通知明日的食安任務';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 取得明天的任務
        $tasks = \App\Models\Task::whereDate('task_date', now()->addDay()->toDateString())->with(['users', 'restaurant', 'meals', 'projects'])->get();
        $url = 'https://foodsafety.feastogether.com.tw/';
        // 開發人員email
        $developer = 'liam.li@eatogether.com.tw';
        // 寄送信件
        foreach ($tasks as $task) {
            foreach ($task->users as $user) {
                // 將開發人員email加入收件人
                $mailTo = [$user->email, $developer];
                $mailTo = array_filter($mailTo, function ($email) {
                    return filter_var($email, FILTER_VALIDATE_EMAIL);
                });

                Mail::to($mailTo)->send(new TaskMailable($task, $user, $url));
            }
        }

        $this->info('Emails sent successfully!');
    }
}
