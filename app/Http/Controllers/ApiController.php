<?php

namespace App\Http\Controllers;

use PDO;
use Carbon\Carbon;
use App\Models\Meal;
use App\Models\Task;
use App\Models\User;
use App\Models\Defect;
use App\Models\Project;
use App\Models\SysPerson;
use App\Models\Restaurant;
use App\Imports\TaskImport;
use App\Models\ClearDefect;
use App\Models\QualityMeal;
use App\Models\QualityTask;
use App\Imports\MealsImport;
use Illuminate\Http\Request;
use App\Models\PosDepartment;
use App\Models\QualityDefect;
use App\Models\TaskHasDefect;
use App\Imports\DefectsImport;
use App\Imports\ClearDefectImport;
use App\Imports\QualityTaskImport;
use App\Models\QualityClearDefect;
use App\Models\TaskHasClearDefect;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Imports\QualityMealsImport;
use App\Models\RestaurantWorkspace;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\QualityDefectsImport;
use App\Imports\QualityClearDefectsImport;
use App\Models\QualityTaskHasQualityDefect;
use App\Models\QualityTaskHasQualityClearDefect;
use Illuminate\Support\Facades\Log;

class ApiController extends Controller
{
    // getUsers
    public function getUsers()
    {
        $users = User::with('roles')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    // getRoles
    public function getRoles()
    {
        $roles = Role::all();

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    // update user role
    public function updateUserRoles(User $user, Request $request)
    {
        $user->syncRoles($request->input('roles'));

        return response()->json([
            'status' => 'success',
            'data' => $user,
        ]);
    }

    // syncUsers
    public function syncUsers()
    {
        // where department_id like 14%
        $users = SysPerson::where('department_id', 'like', '14%')->get();

        $users->transform(function ($user) {
            return [
                'uid' => $user->person_empid,
                'name' => $user->person_name,
                'email' => $user->email,
                'department' => $user->department_name,
                'department_serial' => $user->department_serial,
                'password' => Hash::make($user->person_id_no, ['rounds' => 4]),
                'status' => $user->person_status,
            ];
        });

        // 在 upsert 之前獲取資料
        $originalUsers = User::all();

        User::upsert($users->toArray(), ['uid'], ['name', 'email', 'department', 'department_serial', 'password', 'status']);

        // 在 upsert 之後獲取資料
        $updatedUsers = User::all();

        // 比較兩個集合的差異
        $updatedData = $updatedUsers->diff($originalUsers);

        // 顯示更新的數量
        $message = $updatedData->count() . ' 筆同仁資料已更新';

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    // syncRestaurants
    public function syncRestaurants()
    {
        $restaurants = PosDepartment::where('pos_type', 0)->orWhere('pos_type', 1)->get();
        $restaurants->transform(function ($restaurant) {
            return [
                'sid' => $restaurant->department_ch_id,
                'brand' => $restaurant->department_type_name,
                'brand_code' => $restaurant->department_type_code,
                'shop' => $restaurant->survey_name,
                'location' => $restaurant->area,
                'status' => $restaurant->pos_status,
            ];
        });
        // 在 upsert 之前獲取資料
        $originalRestaurants = Restaurant::all();

        Restaurant::upsert($restaurants->toArray(), ['sid'], ['brand', 'brand_code', 'shop', 'location', 'status']);

        // 在 upsert 之後獲取資料
        $updatedRestaurants = Restaurant::all();

        // 比較兩個集合的差異
        $updatedData = $updatedRestaurants->diff($originalRestaurants);

        // 顯示更新的數量
        $message = $updatedData->count() . ' 筆門市資料已更新';

        return response()->json([
            'status' => 'success',
            'message' => $message,
        ]);
    }

    // 取得有權限 execute-task 的使用者
    public function getExecuteTaskUsers()
    {
        $users = User::permission('execute-task')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    // getExecuteQualityTaskUsers
    public function getExecuteQualityTaskUsers()
    {
        $users = User::permission('execute-quality-task')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    // getRestaurants
    public function getRestaurants(Request $request)
    {
        $is_group_by_brand = $request->input('is_group_by_brand');
        $is_group_by_brand_code = $request->input('is_group_by_brand_code');
        $status = $request->input('status');
        $is_quality = $request->input('is_quality');
        $is_food_safety = $request->input('is_food_safety');

        $restaurants = Restaurant::with('restaurantWorkspaces');

        if ($status) {
            $restaurants = $restaurants->where('status', $status);
        }

        if ($is_food_safety) {
            // 另外取得sid 是 CTK100 CTC100 的餐廳
            $restaurants = $restaurants->where('brand_code', 'not like', 'CT%')->orWhere('sid', 'CTK100')->orWhere('sid', 'CTC100');
        }

        if ($is_quality) {
            $restaurants = $restaurants->where('brand_code', 'like', 'CT%');
        }

        $restaurants = $restaurants->get();


        if ($is_group_by_brand) {
            $restaurants = $restaurants->groupBy('brand');
        }

        if ($is_group_by_brand_code) {
            $restaurants = $restaurants->groupBy('brand_code');
        }


        return response()->json([
            'status' => 'success',
            'data' => $restaurants,
        ]);
    }

    // storeRestaurant
    public function storeRestaurant(Request $request)
    {
        // 如果sid有重複就不新增
        $restaurant = Restaurant::where('sid', $request->input('sid'))->first();

        if ($restaurant) {
            return response()->json([
                'status' => 'error',
                'message' => '品牌店代碼重複',
            ]);
        }

        $restaurant = Restaurant::create($request->all());

        // 預設新增一個區站
        $restaurant->restaurantWorkspaces()->create([
            'area' => '無',
            'status' => 1,
            'category_value' => $restaurant->sid,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $restaurant,
        ]);
    }

    // storeRestaurantWorkspace
    public function storeRestaurantWorkspace(Restaurant $restaurant, Request $request)
    {
        $restaurant->restaurantWorkspaces()->create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $restaurant->restaurantWorkspaces,
        ]);
    }

    // updateRestaurantWorkspace
    public function updateRestaurantWorkspace(RestaurantWorkspace $restaurantWorkspace, Request $request)
    {
        $restaurantWorkspace->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $restaurantWorkspace,
        ]);
    }


    // 取得所有任務(根據使用者權限)
    public function getTasks(Request $request)
    {
        $status = $request->input('status');
        $year = $request->input('year');
        $month = $request->input('month');
        // 如果有 view-all-task 的權限，才可以看到所有的任務

        if (auth()->user()->can('view-all-task')) {
            $tasks = Task::with(['restaurant', 'users', 'meals', 'projects'])
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($year, function ($query, $year) {
                    return $query->whereYear('task_date', $year);
                })
                ->when($month, function ($query, $month) {
                    return $query->whereMonth('task_date', $month);
                })
                ->orderBy('task_date')
                ->get();
        } else {
            $tasks = auth()->user()->tasks()->with(['restaurant', 'users', 'meals', 'projects'])
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })->when($year, function ($query, $year) {
                    return $query->whereYear('task_date', $year);
                })
                ->when($month, function ($query, $month) {
                    return $query->whereMonth('task_date', $month);
                })
                ->orderBy('task_date')
                ->get();
        }


        return response()->json([
            'status' => 'success',
            'data' => $tasks,
        ]);
    }

    // getQualityTasks
    public function getQualityTasks(Request $request)
    {
        $status = $request->input('status');
        $year = $request->input('year');
        $month = $request->input('month');
        // 如果有 view-all-task 的權限，才可以看到所有的任務
        if (auth()->user()->can('view-all-task')) {
            $tasks = QualityTask::with(['restaurant', 'users', 'meals'])
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($year, function ($query, $year) {
                    return $query->whereYear('task_date', $year);
                })
                ->when($month, function ($query, $month) {
                    return $query->whereMonth('task_date', $month);
                })
                ->orderBy('task_date')
                ->get();
        } else {
            $tasks = auth()->user()->qualityTasks()->with(['restaurant', 'users', 'meals'])
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($year, function ($query, $year) {
                    return $query->whereYear('task_date', $year);
                })
                ->when($month, function ($query, $month) {
                    return $query->whereMonth('task_date', $month);
                })
                ->orderBy('task_date')
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $tasks,
        ]);
    }

    // 儲存任務
    public function storeTask(Request $request)
    {
        $date = $request->input('date');
        $time = $request->input('time');
        $users = $request->input('users');
        $restaurant = $request->input('restaurant');
        $meals = $request->input('meals');
        $projects = $request->input('projects');
        $category = $request->input('category');

        $task = Task::create([
            'category' => $category,
            'restaurant_id' => $restaurant['id'],
            'task_date' => $date . ' ' . $time,
        ]);

        foreach ($users as $user) {
            $task->users()->attach($user['id']);
        }

        foreach ($meals as $meal) {
            $task->meals()->attach($meal['id']);
        }

        // 如果是餐點採樣，就不用指派專案
        if ($category != '餐點採樣') {
            foreach ($projects as $project) {
                $task->projects()->attach($project['id']);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 儲存品保任務
    public function storeQualityTask(Request $request)
    {
        $date = $request->input('date');
        $time = $request->input('time');
        $users = $request->input('users');
        $restaurant = $request->input('restaurant');
        $meals = $request->input('meals');
        $category = $request->input('category');

        $task = QualityTask::create([
            'category' => $category,
            'restaurant_id' => $restaurant['id'],
            'task_date' => $date . ' ' . $time,
        ]);

        foreach ($users as $user) {
            $task->users()->attach($user['id']);
        }

        foreach ($meals as $meal) {
            $task->meals()->attach($meal['id']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 更新任務
    public function updateTask(Task $task, Request $request)
    {
        $date = $request->input('date');
        $time = $request->input('time');
        $users = $request->input('users');
        $restaurant = $request->input('restaurant');
        $meals = $request->input('meals');
        $projects = $request->input('projects');
        $category = $request->input('category');

        $task->update([
            'category' => $category,
            'restaurant_id' => $restaurant['id'],
            'task_date' => $date . ' ' . $time,
        ]);

        $task->users()->sync([]);
        foreach ($users as $user) {
            $task->users()->attach($user['id']);
        }

        $task->meals()->sync([]);
        foreach ($meals as $meal) {
            $task->meals()->attach($meal['id']);
        }

        // 如果是餐點採樣，就不用指派專案
        if ($category != '餐點採樣') {
            $task->projects()->sync([]);
            foreach ($projects as $project) {
                $task->projects()->attach($project['id']);
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 更新品保任務
    public function updateQualityTask(QualityTask $task, Request $request)
    {
        $date = $request->input('date');
        $time = $request->input('time');
        $users = $request->input('users');
        $restaurant = $request->input('restaurant');
        $meals = $request->input('meals');
        $category = $request->input('category');

        $task->update([
            'category' => $category,
            'restaurant_id' => $restaurant['id'],
            'task_date' => $date . ' ' . $time,
        ]);

        $task->users()->sync([]);
        foreach ($users as $user) {
            $task->users()->attach($user['id']);
        }

        $task->meals()->sync([]);
        foreach ($meals as $meal) {
            $task->meals()->attach($meal['id']);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 刪除任務
    public function deleteTask(Task $task)
    {
        $task->users()->detach();
        $task->meals()->detach();
        $task->projects()->detach();
        $task->delete();
        // 刪除任務的缺失
        $task->taskHasDefects()->delete();
        // 刪除任務的清檢缺失
        $task->taskHasClearDefects()->delete();


        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 刪除品保任務
    public function deleteQualityTask(QualityTask $task)
    {
        $task->users()->detach();
        $task->meals()->detach();
        $task->delete();

        $task->taskHasDefects()->delete();
        $task->taskHasClearDefects()->delete();

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 匯入任務
    public function importTasks(Request $request)
    {
        Excel::import(new TaskImport, $request->file('file'));
        return response()->json([
            'status' => 'success',
        ]);
    }

    // 匯入品保任務
    public function importQualityTasks(Request $request)
    {
        Excel::import(new QualityTaskImport, $request->file('file'));
        return response()->json([
            'status' => 'success',
        ]);
    }

    // 取得該月未被指派到的餐廳
    public function getUnassignedRestaurants(Request $request)
    {
        $date = $request->input('date');
        $date = Carbon::create($date);
        $is_quality = $request->input('is_quality');

        $restaurants = Restaurant::where('status', true);
        if ($is_quality) {
            $restaurants = $restaurants->where('brand_code', 'like', 'CT%');
            $restaurants->whereDoesntHave('qualityTasks', function ($query) use ($date) {
                $query->whereYear('task_date', $date->format('Y'))
                    ->whereMonth('task_date', $date->format('m'));
            });
        } else {
            $restaurants = $restaurants->where('brand_code', 'not like', 'CT%');
            $restaurants->whereDoesntHave('tasks', function ($query) use ($date) {
                $query->whereYear('task_date', $date->format('Y'))
                    ->whereMonth('task_date', $date->format('m'));
            });
        }
        $restaurants = $restaurants->get();
        return response()->json([
            'status' => 'success',
            'data' => $restaurants,
        ]);
    }

    // 取得該月份該餐聽的餐點
    public function getRestaurantMeals(Request $request)
    {
        $date = $request->input('date');
        $sid = $request->input('sid');
        $brand_code = $request->input('brand_code');

        $date = Carbon::create($date);

        // 取得sid是EAT007和brand_code是EAT的餐點
        $meals = Meal::whereIn('sid', [$sid, $brand_code])
            ->whereYear('effective_date', $date->format('Y'))
            ->whereMonth('effective_date', $date->format('m'))->get();

        return response()->json([
            'status' => 'success',
            'data' => $meals,
        ]);
    }

    // getRestaurantQualityMeals
    public function getRestaurantQualityMeals(Request $request)
    {
        $date = $request->input('date');
        $sid = $request->input('sid');
        $brand_code = $request->input('brand_code');

        $date = Carbon::create($date);

        // 取得sid是EAT007和brand_code是EAT的餐點
        $meals = QualityMeal::whereIn('sid', [$sid, $brand_code])
            ->whereYear('effective_date', $date->format('Y'))
            ->whereMonth('effective_date', $date->format('m'))->get();

        return response()->json([
            'status' => 'success',
            'data' => $meals,
        ]);
    }

    // 取得啟用的專案
    public function getActiveProjects()
    {
        $projects = Project::where('status', true)->get();

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    // 取得該使用者的任務列表
    public function getUserTasks(Request $request)
    {
        $status = $request->input('status');
        $limit = $request->input('limit');
        // status 有才要過濾
        $tasks = auth()->user()->tasks()->with(['restaurant', 'users', 'meals', 'projects'])
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->get();

        $tasks = $tasks->sortBy(function ($task) {
            return abs(Carbon::parse($task->task_date)->diffInMinutes(now()));
        });
        // 取得總數
        $total = $tasks->count();

        // 重新排序 分頁
        $tasks = $tasks->values()->forPage(1, $limit);


        return response()->json([
            'status' => 'success',
            'data' => $tasks,
            'total' => $total,
        ]);
    }

    // 取得該使用者的品保任務列表
    public function getUserQualityTasks(Request $request)
    {
        $status = $request->input('status');
        $limit = $request->input('limit');
        // status 有才要過濾
        $tasks = auth()->user()->qualityTasks()->with(['restaurant', 'users', 'meals'])
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->get();

        $tasks = $tasks->sortBy(function ($task) {
            return abs(Carbon::parse($task->task_date)->diffInMinutes(now()));
        });
        // 取得總數
        $total = $tasks->count();

        // 重新排序 分頁
        $tasks = $tasks->values()->forPage(1, $limit);

        return response()->json([
            'status' => 'success',
            'data' => $tasks,
            'total' => $total,
        ]);
    }

    // 修改使用者的任務狀態
    public function updateUserTaskStatus(Task $task, Request $request)
    {
        $is_completed = $request->input('is_completed');

        $task->users()->updateExistingPivot(auth()->user()->id, [
            'is_completed' => $is_completed,
        ]);

        if ($task->users()->wherePivot('is_completed', 0)->count() === 0) {
            $task->update([
                'status' => 'pending_approval',
            ]);
        } else {
            $task->update([
                'status' => 'processing',
            ]);
        }

        if ($task->category == '餐點採樣') {
            $task->update([
                'status' => 'completed',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 修改使用者的品保任務狀態
    public function updateUserQualityTaskStatus(QualityTask $task, Request $request)
    {
        $is_completed = $request->input('is_completed');

        $task->users()->updateExistingPivot(auth()->user()->id, [
            'is_completed' => $is_completed,
        ]);

        if ($task->users()->wherePivot('is_completed', 0)->count() === 0) {
            $task->update([
                'status' => 'pending_approval',
            ]);
        } else {
            $task->update([
                'status' => 'processing',
            ]);
        }

        if ($task->category == '食材/成品採樣') {
            $task->update([
                'status' => 'completed',
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 確認此任務是否所有人員已完成
    public function isAllCompleted(Task $task)
    {
        $isAllCompleted = $task->users()->wherePivot('is_completed', 0)->count() === 0;

        return response()->json([
            'status' => 'success',
            'data' => $isAllCompleted,
        ]);
    }

    // 確認此品保任務是否所有人員已完成
    public function isQualityAllCompleted(QualityTask $task)
    {
        $isAllCompleted = $task->users()->wherePivot('is_completed', 0)->count() === 0;

        return response()->json([
            'status' => 'success',
            'data' => $isAllCompleted,
        ]);
    }

    // 修改任務的多筆專案是否查核
    public function updateTaskProjectStatus(Task $task, Request $request)
    {
        $projects = $request->input('projects');

        foreach ($projects as $project) {
            $task->projects()->updateExistingPivot($project['id'], [
                'is_checked' => $project['pivot']['is_checked'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 修改任務的多筆採樣是否帶回和備註
    public function updateTaskMealStatus(Task $task, Request $request)
    {
        $meals = $request->input('meals');

        foreach ($meals as $meal) {
            $task->meals()->updateExistingPivot($meal['id'], [
                'is_taken' => $meal['pivot']['is_taken'],
                'memo' => $meal['pivot']['memo'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 修改品保任務的多筆採樣是否帶回和備註
    public function updateQualityTaskMealStatus(QualityTask $task, Request $request)
    {
        $meals = $request->input('meals');

        foreach ($meals as $meal) {
            $task->meals()->updateExistingPivot($meal['id'], [
                'is_taken' => $meal['pivot']['is_taken'],
                'memo' => $meal['pivot']['memo'],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 取得任務相關資料
    public function getTask(Task $task)
    {
        // restaurantWorkspaces只取啟用的
        $task->load([
            'restaurant.restaurantWorkspaces' => function ($query) {
                $query->where('status', 1);
            },
            'users',
            'meals',
            'projects'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // 取得品保相關資料
    public function getQualityTask(QualityTask $task)
    {
        // restaurantWorkspaces只取啟用的
        $task->load([
            'restaurant.restaurantWorkspaces' => function ($query) {
                $query->where('status', 1);
            },
            'users',
            'meals'
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    // getRestaurantsWorkSpaces
    public function getRestaurantsWorkSpaces(Request $request)
    {
        $restaurant_id = $request->input('restaurant_id');

        $restaurant = Restaurant::with('restaurantWorkspaces')->find($restaurant_id);

        return response()->json([
            'status' => 'success',
            'data' => $restaurant,
        ]);
    }

    // 取得該月啟用的食安缺失條文
    public function getActiveDefects()
    {
        $thisMonth = Carbon::today()->firstOfMonth()->format('Y-m-d');

        $activeDate = Defect::where('effective_date', '<=', $thisMonth)->orderBy('effective_date', 'desc')->first()->effective_date;
        $activeDate = Carbon::create($activeDate);
        $defects = Defect::whereYear('effective_date', $activeDate)->whereMonth('effective_date', $activeDate)->get();

        // 分二層 從group -> title
        $defects = $defects->groupBy('group')->map(function ($group) {
            return $group->groupBy('title');
        });

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得該月啟用的品保缺失條文
    public function getActiveQualityDefects()
    {
        $thisMonth = Carbon::today()->firstOfMonth()->format('Y-m-d');

        $activeDate = QualityDefect::where('effective_date', '<=', $thisMonth)->orderBy('effective_date', 'desc')->first()->effective_date;
        $activeDate = Carbon::create($activeDate);
        $defects = QualityDefect::whereYear('effective_date', $activeDate)->whereMonth('effective_date', $activeDate)->get();

        // 分二層 從group -> title
        $defects = $defects->groupBy('group')->map(function ($group) {
            return $group->groupBy('title');
        });

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得該月啟用的清檢缺失條文
    public function getActiveClearDefects()
    {
        $thisMonth = Carbon::today()->firstOfMonth()->format('Y-m-d');

        $activeDate = ClearDefect::where('effective_date', '<=', $thisMonth)->orderBy('effective_date', 'desc')->first()->effective_date;
        $activeDate = Carbon::create($activeDate);
        $defects = ClearDefect::whereYear('effective_date', $activeDate)->whereMonth('effective_date', $activeDate)->get();

        // 分一層 從main_item
        $defects = $defects->groupBy('main_item');

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得該月啟用的品保清檢缺失條文
    public function getActiveQualityClearDefects()
    {
        $thisMonth = Carbon::today()->firstOfMonth()->format('Y-m-d');

        $activeDate = QualityClearDefect::where('effective_date', '<=', $thisMonth)->orderBy('effective_date', 'desc')->first()->effective_date;
        $activeDate = Carbon::create($activeDate);
        $defects = QualityClearDefect::whereYear('effective_date', $activeDate)->whereMonth('effective_date', $activeDate)->get();

        // 分一層 從main_item
        $defects = $defects->groupBy('main_item');

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得該任務食安缺失資料依照區站分類
    public function getTaskDefects(Task $task)
    {
        $defects = $task->load('taskHasDefects.restaurantWorkspace', 'taskHasDefects.defect', 'taskHasDefects.user')->taskHasDefects
            ->each(function ($defect) {
                $defect->append('images_url');
            })
            ->groupBy('restaurantWorkspace.area');

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // getQualityTaskDefects
    public function getQualityTaskDefects(QualityTask $task)
    {
        $defects = $task->load('taskHasDefects.restaurantWorkspace', 'taskHasDefects.defect', 'taskHasDefects.user')->taskHasDefects
            ->each(function ($defect) {
                $defect->append('images_url');
            })
            ->groupBy('restaurantWorkspace.area');

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得該任務清檢缺失資料依照區站分類
    public function getTaskClearDefects(Task $task)
    {
        $defects = $task->load('taskHasClearDefects.restaurantWorkspace', 'taskHasClearDefects.clearDefect', 'taskHasClearDefects.user')->taskHasClearDefects
            ->each(function ($defect) {
                $defect->append('images_url');
            })
            ->groupBy('restaurantWorkspace.area');

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // getQualityTaskClearDefects
    public function getQualityTaskClearDefects(QualityTask $task)
    {
        $defects = $task->load('taskHasClearDefects.restaurantWorkspace', 'taskHasClearDefects.clearDefect', 'taskHasClearDefects.user')->taskHasClearDefects
            ->each(function ($defect) {
                $defect->append('images_url');
            })
            ->groupBy('restaurantWorkspace.area');

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 更新任務的食安缺失資料 /api/tasks/{{ $task->id }}/defects/${this.editedItem.id}
    public function updateTaskDefect(TaskHasDefect $taskHasDefect, Request $request)
    {
        // 假如已經有主管簽名的話，就不能修改，除非有update-task權限
        if ($taskHasDefect->task->status == 'completed' && !auth()->user()->can('update-task')) {
            return response()->json([
                'status' => 'error',
                'message' => '已經有主管簽名，無法修改',
            ]);
        }

        $taskHasDefect->update([
            'restaurant_workspace_id' => $request->input('restaurant_workspace_id'),
            'defect_id' => $request->input('defect_id'),
            'memo' => $request->input('memo'),
            'is_ignore' => $request->input('is_ignore'),
            'is_not_reach_deduct_standard' => $request->input('is_not_reach_deduct_standard'),
            'is_suggestion' => $request->input('is_suggestion'),
            'is_repeat' => $request->input('is_repeat'),
        ]);

        $taskHasDefect = $taskHasDefect->load('restaurantWorkspace');
        return response()->json([
            'status' => 'success',
            'data' => $taskHasDefect,
        ]);
    }

    // 更新品保任務的食安缺失資料 /api/quality-tasks/{{ $task->id }}/defects/${this.editedItem.id}
    public function updateQualityTaskDefect(QualityTaskHasQualityDefect $taskHasDefect, Request $request)
    {
        // 假如已經有主管簽名的話，就不能修改，除非有update-task權限
        if ($taskHasDefect->task->status == 'completed' && !auth()->user()->can('update-task')) {
            return response()->json([
                'status' => 'error',
                'message' => '已經有主管簽名，無法修改',
            ]);
        }

        $taskHasDefect->update([
            'restaurant_workspace_id' => $request->input('restaurant_workspace_id'),
            'quality_defect_id' => $request->input('quality_defect_id'),
            'memo' => $request->input('memo'),
            'is_ignore' => $request->input('is_ignore'),
            'is_not_reach_deduct_standard' => $request->input('is_not_reach_deduct_standard'),
            'is_suggestion' => $request->input('is_suggestion'),
            'is_repeat' => $request->input('is_repeat'),
        ]);

        $taskHasDefect = $taskHasDefect->load('restaurantWorkspace');
        return response()->json([
            'status' => 'success',
            'data' => $taskHasDefect,
        ]);
    }

    // 更新任務的清檢缺失資料
    public function updateTaskClearDefect(TaskHasClearDefect $taskHasClearDefect, Request $request)
    {
        // 假如已經有主管簽名的話，就不能修改，除非有update-task權限
        if ($taskHasClearDefect->task->status == 'completed' && !auth()->user()->can('update-task')) {
            return response()->json([
                'status' => 'error',
                'message' => '已經有主管簽名，無法修改',
            ]);
        }

        $taskHasClearDefect->update([
            'restaurant_workspace_id' => $request->input('restaurant_workspace_id'),
            'clear_defect_id' => $request->input('clear_defect_id'),
            'memo' => $request->input('memo'),
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'is_ignore' => $request->input('is_ignore'),
            'is_not_reach_deduct_standard' => $request->input('is_not_reach_deduct_standard'),
            'is_suggestion' => $request->input('is_suggestion'),
        ]);

        $taskHasClearDefect = $taskHasClearDefect->load('restaurantWorkspace');
        return response()->json([
            'status' => 'success',
            'data' => $taskHasClearDefect,
        ]);
    }

    // 更新品保任務的清檢缺失資料
    public function updateQualityTaskClearDefect(QualityTaskHasQualityClearDefect $taskHasClearDefect, Request $request)
    {
        // 假如已經有主管簽名的話，就不能修改，除非有update-task權限
        if ($taskHasClearDefect->task->status == 'completed' && !auth()->user()->can('update-task')) {
            return response()->json([
                'status' => 'error',
                'message' => '已經有主管簽名，無法修改',
            ]);
        }
        $taskHasClearDefect->update([
            'restaurant_workspace_id' => $request->input('restaurant_workspace_id'),
            'quality_clear_defect_id' => $request->input('quality_clear_defect_id'),
            'memo' => $request->input('memo'),
            'amount' => $request->input('amount'),
            'description' => $request->input('description'),
            'is_ignore' => $request->input('is_ignore'),
            'is_not_reach_deduct_standard' => $request->input('is_not_reach_deduct_standard'),
            'is_suggestion' => $request->input('is_suggestion'),
        ]);

        $taskHasClearDefect = $taskHasClearDefect->load('restaurantWorkspace');
        return response()->json([
            'status' => 'success',
            'data' => $taskHasClearDefect,
        ]);
    }

    public function updateTaskBoss(Task $task, Request $request)
    {
        $task->update([
            'inner_manager' => $request->input('inner_manager'),
            'outer_manager' => $request->input('outer_manager'),
            'status' => 'completed',
            'end_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    public function updateQualityTaskBoss(QualityTask $task, Request $request)
    {
        $task->update([
            'inner_manager' => $request->input('inner_manager'),
            'outer_manager' => $request->input('outer_manager'),
            'status' => 'completed',
            'end_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $task,
        ]);
    }

    public function getTaskScore(Task $task)
    {
        $task->load('taskHasDefects.defect');
        // 計算內場分數 taskHasDefects.taskHasDefects where area not like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0, is_repeat=0
        $totalInnerScore = 0;
        foreach ($task->taskHasDefects as $defect) {
            if ($defect->restaurantWorkspace->area != '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0 && $defect->is_repeat == 0) {
                $totalInnerScore += $defect->defect->deduct_point;
            }
        }

        // 計算外場分數 taskHasDefects.taskHasDefects where area like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0, is_repeat=0
        $totalOuterScore = 0;
        foreach ($task->taskHasDefects as $defect) {
            if ($defect->restaurantWorkspace->area == '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0 && $defect->is_repeat == 0) {
                $totalOuterScore += $defect->defect->deduct_point;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'inner_score' => 100 + $totalInnerScore,
                'outer_score' => 100 + $totalOuterScore,
            ],
        ]);
    }

    public function getQualityTaskScore(QualityTask $task)
    {
        $task->load('taskHasDefects.defect');
        // 計算內場分數 taskHasDefects.taskHasDefects where area not like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0, is_repeat=0
        $totalInnerScore = 0;
        foreach ($task->taskHasDefects as $defect) {
            if ($defect->restaurantWorkspace->area != '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0 && $defect->is_repeat == 0) {
                $totalInnerScore += $defect->defect->deduct_point;
            }
        }

        // 計算外場分數 taskHasDefects.taskHasDefects where area like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0, is_repeat=0
        $totalOuterScore = 0;
        foreach ($task->taskHasDefects as $defect) {
            if ($defect->restaurantWorkspace->area == '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0 && $defect->is_repeat == 0) {
                $totalOuterScore += $defect->defect->deduct_point;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'inner_score' => 100 + $totalInnerScore,
                'outer_score' => 100 + $totalOuterScore,
            ],
        ]);
    }

    public function getTaskClearScore(Task $task)
    {

        $task->load('taskHasClearDefects');

        // 計算內場分數 taskHasDefects.taskHasDefects where area not like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0
        $totalInnerScore = 0;
        foreach ($task->taskHasClearDefects as $defect) {
            if ($defect->restaurantWorkspace->area != '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0) {
                $totalInnerScore += $defect->clearDefect->deduct_point * $defect->amount;
            }
        }

        // 計算外場分數 taskHasDefects.taskHasDefects where area like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0
        $totalOuterScore = 0;
        foreach ($task->taskHasClearDefects as $defect) {
            if ($defect->restaurantWorkspace->area == '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0) {
                $totalOuterScore += $defect->clearDefect->deduct_point * $defect->amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'inner_score' => 100 + $totalInnerScore,
                'outer_score' => 100 + $totalOuterScore,
            ],
        ]);
    }

    // getQualityTaskClearScore
    public function getQualityTaskClearScore(QualityTask $task)
    {

        $task->load('taskHasClearDefects');

        // 計算內場分數 taskHasDefects.taskHasDefects where area not like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0
        $totalInnerScore = 0;
        foreach ($task->taskHasClearDefects as $defect) {
            if ($defect->restaurantWorkspace->area != '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0 && $defect->is_repeat == 0) {
                $totalInnerScore += $defect->clearDefect->deduct_point * $defect->amount;
            }
        }

        // 計算外場分數 taskHasDefects.taskHasDefects where area like "%外場"
        // is_ignore = 0 ,is_not_reach_deduct_standard=0, is_suggestion=0
        $totalOuterScore = 0;
        foreach ($task->taskHasClearDefects as $defect) {
            if ($defect->restaurantWorkspace->area == '外場' && $defect->is_ignore == 0 && $defect->is_not_reach_deduct_standard == 0 && $defect->is_suggestion == 0 && $defect->is_repeat == 0) {
                $totalOuterScore += $defect->clearDefect->deduct_point * $defect->amount;
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'inner_score' => 100 + $totalInnerScore,
                'outer_score' => 100 + $totalOuterScore,
            ],
        ]);
    }

    public function deleteTaskDefect(TaskHasDefect $taskHasDefect)
    {
        $taskHasDefect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $taskHasDefect,
        ]);
    }

    // deleteQualityTaskDefect
    public function deleteQualityTaskDefect(QualityTaskHasQualityDefect $taskHasDefect)
    {
        $taskHasDefect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $taskHasDefect,
        ]);
    }

    public function deleteTaskClearDefect(TaskHasClearDefect $taskHasClearDefect)
    {
        $taskHasClearDefect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $taskHasClearDefect,
        ]);
    }

    // deleteQualityTaskClearDefect
    public function deleteQualityTaskClearDefect(QualityTaskHasQualityClearDefect $taskHasClearDefect)
    {
        $taskHasClearDefect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $taskHasClearDefect,
        ]);
    }

    public function getMeals()
    {
        $meals = Meal::all();
        $meals = $meals->map(function ($meal) {
            $meal->effective_date = Carbon::create($meal->effective_date)->format('Y-m');
            return $meal;
        });
        return response()->json([
            'status' => 'success',
            'data' => $meals,
        ]);
    }

    // 取得品保的餐點
    public function getQualityMeals()
    {
        $meals = QualityMeal::get();
        $meals = $meals->map(function ($meal) {
            $meal->effective_date = Carbon::create($meal->effective_date)->format('Y-m');
            return $meal;
        });
        return response()->json([
            'status' => 'success',
            'data' => $meals,
        ]);
    }

    public function storeMeal(Request $request)
    {

        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $meal = Meal::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $meal,
        ]);
    }

    public function storeQualityMeal(Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $meal = QualityMeal::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $meal,
        ]);
    }

    public function updateMeal(Meal $meal, Request $request)
    {
        // carbon effective_date
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $meal->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $meal,
        ]);
    }

    public function updateQualityMeal(QualityMeal $meal, Request $request)
    {
        // carbon effective_date
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $meal->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $meal,
        ]);
    }

    public function deleteMeal(Meal $meal)
    {
        $meal->delete();

        return response()->json([
            'status' => 'success',
            'data' => $meal,
        ]);
    }

    public function deleteQualityMeal(QualityMeal $meal)
    {
        $meal->delete();

        return response()->json([
            'status' => 'success',
            'data' => $meal,
        ]);
    }

    public function importMeals()
    {
        try {
            Excel::import(new MealsImport, request()->file('file'));
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function importQualityMeals()
    {
        try {
            Excel::import(new QualityMealsImport, request()->file('file'));
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function getMealRecords(Request $request)
    {
        $dates = $request->input('dates');
        $isTaken = $request->input('is_taken') === 'true' ? 1 : 0;
        sort($dates);
        $dates[1] = Carbon::create($dates[1])->addDay()->format('Y-m-d');
        // 篩選任務有包含餐點的 任務日期是在dates範圍裡面
        $tasks = Task::whereBetween('task_date', $dates)->whereHas('meals')->with(['restaurant', 'meals'])->get();

        $mealRecords = [];

        foreach ($tasks as $task) {
            // 假如isTaken是依才要篩選
            if ($isTaken) {
                $task->meals = $task->meals->filter(function ($meal) {
                    return $meal->pivot->is_taken === 1;
                });
            }
            foreach ($task->meals as $meal) {

                $task_date = Carbon::create($task->task_date)->format('Y-m-d');
                $mealRecords[] = [
                    'task_id' => $task->id,
                    'task_date' => $task_date,
                    'restaurant_id' => $task->restaurant_id,
                    'restaurant_brand' => $task->restaurant->brand,
                    'restaurant_shop' => $task->restaurant->shop,
                    'restaurant_sid' => $task->restaurant->sid,
                    'meal_id' => $meal->id,
                    'meal_name' => $meal->name,
                    'meal_sid' => $meal->sid,
                    'meal_effective_month' => Carbon::create($meal->effective_date)->format('Y-m'),
                    'meal_category' => $meal->category,
                    'meal_chef' => $meal->chef,
                    'meal_workspace' => $meal->workspace,
                    'meal_qno' => $meal->qno,
                    'meal_note' => $meal->note,
                    'meal_item' => $meal->item,
                    'meal_items' => $meal->items,
                    'is_taken' => $meal->pivot->is_taken,
                    'memo' => $meal->pivot->memo,
                ];
            }
        }

        // 重新排序根據task_date
        usort($mealRecords, function ($a, $b) {
            return $a['task_date'] <=> $b['task_date'];
        });

        return response()->json([
            'status' => 'success',
            'data' => $mealRecords,
        ]);
    }

    public function getQualityMealRecords(Request $request)
    {
        $dates = $request->input('dates');
        sort($dates);
        $dates[1] = Carbon::create($dates[1])->addDay()->format('Y-m-d');

        // 篩選任務有包含餐點的 任務日期是在dates範圍裡面
        $tasks = QualityTask::whereBetween('task_date', $dates)->whereHas('meals')->with(['restaurant', 'meals'])->get();

        $mealRecords = [];

        foreach ($tasks as $task) {
            foreach ($task->meals as $meal) {
                $task_date = Carbon::create($task->task_date)->format('Y-m-d');
                $mealRecords[] = [
                    'task_id' => $task->id,
                    'task_date' => $task_date,
                    'restaurant_sid' => $task->restaurant->sid,
                    'restaurant_id' => $task->restaurant_id,
                    'restaurant_brand' => $task->restaurant->brand,
                    'restaurant_shop' => $task->restaurant->shop,
                    'meal_id' => $meal->id,
                    'meal_name' => $meal->name,
                    'meal_sid' => $meal->sid,
                    'meal_effective_month' => Carbon::create($meal->effective_date)->format('Y-m'),
                    'meal_category' => $meal->category,
                    'meal_chef' => $meal->chef,
                    'meal_workspace' => $meal->workspace,
                    'meal_qno' => $meal->qno,
                    'meal_note' => $meal->note,
                    'meal_item' => $meal->item,
                    'meal_items' => $meal->items,
                    'is_taken' => $meal->pivot->is_taken,
                    'memo' => $meal->pivot->memo,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $mealRecords,
        ]);
    }

    // 取得專案資料
    public function getProjects()
    {
        $projects = Project::all();

        return response()->json([
            'status' => 'success',
            'data' => $projects,
        ]);
    }

    // 取得月份的專案缺失資料
    public function getProjectDefects()
    {
        $month = request()->input('month');
        $month = Carbon::create($month);

        $defects = Defect::whereYear('effective_date', $month->format('Y'))
            ->whereMonth('effective_date', $month->format('m'))
            ->where('category', '專案查核')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 新增專案
    public function storeProject(Request $request)
    {
        $project = Project::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }

    // 更新專案
    public function updateProject(Project $project, Request $request)
    {
        $project->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $project,
        ]);
    }

    // 取得食安的食安缺失資料
    public function getDefects()
    {
        $month = request()->input('month');
        // 2024-03 or null 假如是null 就不用撈全部

        $defects = Defect::where('effective_date', 'like', $month . '%')->get();
        $defects = $defects->map(function ($defect) {
            $defect->effective_date = Carbon::create($defect->effective_date)->format('Y-m');
            return $defect;
        });

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得品保的食安缺失資料
    public function getQualityDefects()
    {
        $defect = QualityDefect::all();
        $defect = $defect->map(function ($defect) {
            $defect->effective_date = Carbon::create($defect->effective_date)->format('Y-m');
            return $defect;
        });

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 新增食安缺失資料
    public function storeDefect(Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $defect = Defect::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 新增品保食安條文資料
    public function storeQualityDefect(Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $defect = QualityDefect::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 更新食安缺失資料
    public function updateDefect(Defect $defect, Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $defect->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 更新品保食安缺失資料
    public function updateQualityDefect(QualityDefect $defect, Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $defect->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 刪除食安缺失資料
    public function deleteDefect(Defect $defect)
    {
        $defect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 刪除品保食安缺失資料
    public function deleteQualityDefect(QualityDefect $defect)
    {
        $defect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 匯入食安缺失資料
    public function importDefects()
    {
        try {
            Excel::import(new DefectsImport, request()->file('file'));
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // 匯入品保食安缺失資料
    public function importQualityDefects()
    {
        try {
            Excel::import(new QualityDefectsImport, request()->file('file'));
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // 取得清檢缺失資料
    public function getClearDefects()
    {
        $defects = ClearDefect::all();
        $defects = $defects->map(function ($defect) {
            $defect->effective_date = Carbon::create($defect->effective_date)->format('Y-m');
            return $defect;
        });

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 取得品保清檢缺失資料
    public function getQualityClearDefects()
    {
        $defects = QualityClearDefect::all();
        $defects = $defects->map(function ($defect) {
            $defect->effective_date = Carbon::create($defect->effective_date)->format('Y-m');
            return $defect;
        });

        return response()->json([
            'status' => 'success',
            'data' => $defects,
        ]);
    }

    // 新增清檢缺失資料
    public function storeClearDefect(Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $defect = ClearDefect::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 新增品保清檢缺失資料
    public function storeQualityClearDefect(Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $defect = QualityClearDefect::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $defect,
        ]);
    }

    // 更新清檢缺失資料
    public function updateClearDefect(ClearDefect $clearDefect, Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $clearDefect->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $clearDefect,
        ]);
    }

    // 更新品保清檢缺失資料
    public function updateQualityClearDefect(QualityClearDefect $clearDefect, Request $request)
    {
        $request->merge([
            'effective_date' => Carbon::create($request->input('effective_date')),
        ]);

        $clearDefect->update($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $clearDefect,
        ]);
    }

    // 刪除清檢缺失資料
    public function deleteClearDefect(ClearDefect $clearDefect)
    {
        $clearDefect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $clearDefect,
        ]);
    }

    // 刪除品保清檢缺失資料
    public function deleteQualityClearDefect(QualityClearDefect $clearDefect)
    {
        $clearDefect->delete();

        return response()->json([
            'status' => 'success',
            'data' => $clearDefect,
        ]);
    }

    // 匯入清檢缺失資料
    public function importClearDefects()
    {
        try {
            Excel::import(new ClearDefectImport, request()->file('file'));
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // 匯入品保清檢缺失資料
    public function importQualityClearDefects()
    {
        try {
            Excel::import(new QualityClearDefectsImport, request()->file('file'));
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    // getDefectRecords
    public function getDefectRecords(Request $request)
    {
        $month = $request->input('month');
        $month = Carbon::create($month);


        $defectRecords = TaskHasDefect::whereHas('task', function ($query) use ($month) {
            $query->whereYear('task_date', $month->format('Y'))
                ->whereMonth('task_date', $month->format('m'));
        })->with(['task', 'defect', 'restaurantWorkspace', 'user', 'restaurantWorkspace.restaurant'])->get();


        return response()->json([
            'status' => 'success',
            'data' => $defectRecords,
        ]);
    }

    // getQualityDefectRecords
    public function getQualityDefectRecords(Request $request)
    {
        $month = $request->input('month');
        $month = Carbon::create($month);

        $defectRecords = QualityTaskHasQualityDefect::whereHas('task', function ($query) use ($month) {
            $query->whereYear('task_date', $month->format('Y'))
                ->whereMonth('task_date', $month->format('m'));
        })->with(['task', 'defect', 'restaurantWorkspace', 'user', 'restaurantWorkspace.restaurant'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $defectRecords,
        ]);
    }

    // getClearDefectRecords
    public function getClearDefectRecords(Request $request)
    {
        $month = $request->input('month');
        $month = Carbon::create($month);

        $defectRecords = TaskHasClearDefect::whereHas('task', function ($query) use ($month) {
            $query->whereYear('task_date', $month->format('Y'))
                ->whereMonth('task_date', $month->format('m'));
        })->with(['task', 'clearDefect', 'restaurantWorkspace', 'user', 'restaurantWorkspace.restaurant'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $defectRecords,
        ]);
    }

    // getQualityClearDefectRecords
    public function getQualityClearDefectRecords(Request $request)
    {
        $month = $request->input('month');
        $month = Carbon::create($month);

        $defectRecords = QualityTaskHasQualityClearDefect::whereHas('task', function ($query) use ($month) {
            $query->whereYear('task_date', $month->format('Y'))
                ->whereMonth('task_date', $month->format('m'));
        })->with(['task', 'clearDefect', 'restaurantWorkspace', 'user', 'restaurantWorkspace.restaurant'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $defectRecords,
        ]);
    }
}
