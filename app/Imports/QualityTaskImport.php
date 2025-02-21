<?php

namespace App\Imports;

use TypeError;
use Carbon\Carbon;
use App\Models\Task;
use App\Models\Restaurant;
use App\Models\QualityTask;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class QualityTaskImport implements ToCollection
{
    public function collection(Collection $collection)
    {
        // 類別	同仁	分店	稽核日期

        // 移除標題
        $collection = $collection->forget(0);

        // 移除空白行
        $collection = $collection->reject(function ($item) {
            return $item[0] == null;
        });

        // 轉換資料
        $collection->transform(function ($item) {



            // 假設不是食安及5S或清潔檢查，回傳exception
            if (!in_array($item[0], ['食安巡檢', '清潔檢查', '食材/成品採樣', '原料驗收查核', '製程巡檢'])) {
                throw new \Exception('請確認類別是否正確：' . $item[0]);
            }

            try {
                $restaurant = Restaurant::where('sid', $item[2])->firstOrFail();
            } catch (ModelNotFoundException $e) {
                throw new \Exception('請確認分店代碼是否正確：' . $item[2]);
            }

            $userName = explode(',', $item[1]);
            $userIds = [];
            foreach ($userName as $name) {
                try {
                    $userIds[] = \App\Models\User::where('name', $name)->firstOrFail()->id;
                } catch (ModelNotFoundException $e) {
                    throw new \Exception('請確認同仁姓名是否正確：' . $name);
                }
            }

            try {
                // 將excel 日期轉換為Carbon格式
                $taskDate = Carbon::instance(Date::excelToDateTimeObject($item[3]));
            } catch (TypeError $e) {
                throw new \Exception('請確認稽核日期是否正確：' . $item[3]);
            }

            return [
                'restaurant_id' => $restaurant->id,
                'user_id' => $userIds,
                'category' => $item[0],
                'task_date' => $taskDate,
            ];
        });

        // 新增任務
        foreach ($collection as $item) {

            $task = QualityTask::create([
                'restaurant_id' => $item['restaurant_id'],
                'category' => $item['category'],
                'task_date' => $item['task_date'],
            ]);

            $task->users()->sync($item['user_id']);
            // 此任務月份的餐點採樣資料
            $brandMeals = \App\Models\QualityMeal::whereYear('effective_date', $task->task_date)->whereMonth('effective_date', $task->task_date)->where('sid', $task->restaurant->brand_code)->get();
            $shopMeals = \App\Models\QualityMeal::whereYear('effective_date', $task->task_date)->whereMonth('effective_date', $task->task_date)->where('sid', $task->restaurant->sid)->get();
            $meals = $brandMeals->merge($shopMeals);

            // 將餐點採樣資料和任務關聯
            $task->meals()->sync($meals);
        }
    }
}
