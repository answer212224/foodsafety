<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Mail\QualityTaskMailable;
use Illuminate\Support\Facades\Mail;

class SendQualityEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:quality-send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '通知明日的品保任務';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 取得明天的任務
        $tasks = \App\Models\QualityTask::whereDate('task_date', now()->addDay()->toDateString())->with(['users', 'restaurant'])->get();
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

                Mail::to($mailTo)->send(new QualityTaskMailable($task, $user, $url));
            }
        }


        $this->info('Emails sent successfully!');
    }
}
