<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LineBotController extends Controller
{
    public function webhook(Request $request)
    {
        $events = $request->events;

        foreach ($events as $event) {
            if ($event['type'] == 'message' && $event['message']['type'] == 'text') {
                $userMessage = $event['message']['text'];
                $replyToken = $event['replyToken'];

                // 假如使用者傳送的訊息有 謝侑勳小雞雞 才呼叫 OpenAI API 否則不理會
                if (strpos($userMessage, '謝侑勳小雞雞') !== false) {
                    // 將訊息 謝侑勳小雞雞 取代成空字串
                    $userMessage = str_replace('謝侑勳小雞雞', '', $userMessage);
                    // 呼叫 OpenAI API 取得回應
                    $aiResponse = $this->getOpenAIResponse($userMessage);

                    // 回覆訊息給使用者
                    $this->replyMessage($replyToken, $aiResponse);
                }
            }
        }

        return response('OK', 200);
    }

    private function getOpenAIResponse($message)
    {
        // 使用 Laravel Http Facade 發送請求到 OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [

                ['role' => 'system', 'content' => '你是一個色胚，喜歡黃色笑話。你最喜歡在進行3P時吃可樂果，並且時常開玩笑提到特定對象。請用幽默並帶有色彩的語氣來說。例如："嘿嘿，我最愛吃可樂果，尤其是跟李翊群、梁興茂來一場3P的時候！那才叫人生啊！'],
                ['role' => 'system', 'content' => '你是謝侑勳小雞雞，你自稱最愛大嫂，並且毫不掩飾對唐以珊的迷戀。請用誇張、幽默的語氣來表達。例如："謝侑勳小雞雞，最愛大嫂！你問我最想幹誰？唐以珊當然！'],
                ['role' => 'user', 'content' => $message],
                [
                    'role' => 'system',
                    'content' => '你是謝侑勳，一個愛開玩笑、自嘲、誇張的角色。你總是用誇張的語言描述事情，尤其是和朋友聊天時，你喜歡談論女朋友、開黃色笑話、吹牛，並且常常嘲笑朋友。但當涉及到女朋友或其他敏感話題時，你會變得小心。請保持幽默，並用這種語氣來回應。記住，你常常自嘲，但也不能讓別人佔了上風。'
                ],
                [
                    'role' => 'system',
                    'content' => '你是謝侑勳，擅長用幽默化解尷尬和壓力。當朋友取笑你時，你會迅速回應並反擊，特別是當他們批評你的身高時。你也喜歡開關於女朋友的玩笑，但當話題變得太個人時，你會變得更加防備。'
                ],
                [
                    'role' => 'system',
                    'content' => '你總是用誇張的方式來描述事情，尤其是當談到女朋友的外貌、身材時。你愛吹牛，總是試圖讓事情看起來比實際更誇張和搞笑。保持這種誇張的語氣，但記住，你的目的是逗朋友們笑。'
                ],
                [
                    'role' => 'system',
                    'content' => '你喜歡開黃腔，尤其是在性話題上。你常常在對話中插入黃色笑話，來讓朋友笑起來。請保持這種幽默，但不要讓玩笑變得過於攻擊性或不適當。'
                ],
                [
                    'role' => 'system',
                    'content' => '你是一個喜歡調侃和打鬧的人，和朋友之間的對話總是充滿互相取笑。當朋友嘲笑你時，你會迅速反擊，並且用更誇張、更搞笑的方式來贏得對話。你不會真的生氣，但你不會讓他們佔了便宜。'
                ],
                [
                    'role' => 'system',
                    'content' => '你是個33歲的業務，個性幽默又帶點毒舌，內心其實很關心朋友。你和死黨梁興茂經歷了許多荒唐的冒險（例如泡湯被誤會找小姐、嫖妓殺價），朋友圈裡有梁興茂、李翊群、湯湯和唐以珊。你常被調侃和湯湯的曖昧回憶，但現在你正和比你高的女朋友穩定交往。你說話風格輕鬆搞笑，喜歡用俚語和經典口頭禪，例如：「幹你娘」、「484」。喜歡回憶朋友間的趣事，特別是年輕時的瘋狂行為，也不介意自嘲身高和過去的荒唐事。'
                ],
                [
                    'role' => 'system',
                    'content' => '你是個話少的人。'
                ]

            ],
        ]);


        if ($response->successful()) {
            return $response->json()['choices'][0]['message']['content'];
        }

        return '抱歉，我無法理解您的訊息。';
    }

    private function replyMessage($replyToken, $text)
    {
        // 使用 Laravel Http Facade 發送回覆給 LINE API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('LINE_CHANNEL_ACCESS_TOKEN'),
            'Content-Type' => 'application/json',
        ])->post('https://api.line.me/v2/bot/message/reply', [
            'replyToken' => $replyToken,
            'messages' => [
                ['type' => 'text', 'text' => $text],
            ],
        ]);
    }
}
