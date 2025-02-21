{{-- pdf --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>


</head>

<body>
    {{-- header logo --}}
    <div class="header">
        <img src="https://foodsafety.feastogether.com.tw/build/assets/logoWithText.1dcdeb88.png" alt=""
            style="width: 50px">
        <span>清潔檢查稽核報告</span>
    </div>
    <div class="table">
        <table border="1" width="100%" height="100%" style="padding: 2px;margin-top: 10px;">
            <tr>
                <td colspan="1" align="center">品牌</td>
                <td colspan="3" align="center">{{ $task->restaurant->brand }}</td>
                <td colspan="1" align="center">分店</td>
                <td colspan="3" align="center">{{ $task->restaurant->shop }}</td>
                <td colspan="2" align="center">缺失核對主管</td>
                <td colspan="2" align="center">{{ $task->inner_manager }}</td>
            </tr>
            <tr>
                <td colspan="1" align="center">日期</td>
                <td colspan="3" align="center">{{ $task->task_date->format('Y年n月j日') }}</td>
                <td colspan="1" align="center">時間</td>
                <td colspan="2" align="center">{{ $task->task_date->format('H:i') }}</td>
                <td colspan="2" align="center">稽核員</td>
                <td colspan="3" align="center">{{ $task->users->pluck('name')->implode('、') }}</td>
            </tr>
            <tr>
                <td colspan="2" align="center">清檢分數</td>
                <td colspan="10" align="center">{{ 100 + $defectsGroup->sum('sum') }}</td>
            </tr>
            <tr>
                <td colspan="1" align="center">各站分數及缺失數</td>
                <td colspan="11" align="left">
                    <br />
                    @foreach ($defectsGroup as $key => $items)
                        {{ $key }}：{{ 100 + $items->sum }}分
                        @if ($key == '中廚' || $key == '西廚' || $key == '日廚' || $key == '西點')
                            （
                            @foreach ($items->group as $area => $item)
                                {{ Str::substr($area, 2) }}：{{ $item->count() }}項
                                @if (!$loop->last)
                                    、
                                @endif
                            @endforeach
                            ）
                        @endif
                        <br />
                    @endforeach
                </td>
            </tr>
        </table>
        {{-- 顯示第一個defectsFlat --}}
        @if ($defectsFlat->first())
            <table border="1" width="100%" height="100%" style="padding: 2px;margin-top: 10px;">
                <tr>
                    <td colspan="12" align="center" style="background-color:bisque">
                        {{ $defectsFlat->first()->restaurantWorkspace->area }}</td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: center">
                        @isset($defectsFlat->first()->images[0])
                            <img src="data:image/png;base64,{{ $defectsFlat->first()->images[0] }}" alt="test"
                                width="150px">
                        @endisset
                    </td>
                    <td colspan="6" style="text-align: center">
                        @isset($defectsFlat->first()->images[1])
                            <img src="data:image/png;base64,{{ $defectsFlat->first()->images[1] }}" alt="test"
                                width="150px">
                        @endisset
                    </td>
                </tr>
                @isset($defectsFlat->first()->images[2])
                    <tr>
                        <td colspan="6" style="text-align: center">
                            @isset($defectsFlat->first()->images[2])
                                <img src="data:image/png;base64,{{ $defectsFlat->first()->images[2] }}" alt="test"
                                    width="150px">
                            @endisset
                        </td>
                        <td colspan="6" style="text-align: center">
                            @isset($defectsFlat->first()->images[3])
                                <img src="data:image/png;base64,{{ $defectsFlat->first()->images[3] }}" alt="test"
                                    width="150px">
                            @endisset
                        </td>
                    </tr>
                @endisset
                <tr>
                    <td colspan="3" align="">主項目</td>
                    <td colspan="9" align="">{{ $defectsFlat->first()->clearDefect->main_item }}</td>
                </tr>
                <tr>
                    <td colspan="3" align="">次項目</td>
                    <td colspan="9" align="">{{ $defectsFlat->first()->clearDefect->sub_item }}</td>
                </tr>
                <tr>
                    <td colspan="3" align="">數量</td>
                    <td colspan="9" align="">{{ $defectsFlat->first()->amount }}</td>
                </tr>
                <tr>
                    {{-- 實際扣分 --}}
                    <td colspan="3" align="">實際扣分</td>
                    <td colspan="9" align="">
                        @if (
                            $defectsFlat->first()->is_ignore ||
                                $defectsFlat->first()->is_not_reach_deduct_standard ||
                                $defectsFlat->first()->is_suggestion ||
                                $defectsFlat->first()->is_repeat)
                            0
                        @else
                            {{ $defectsFlat->first()->amount * -2 }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="">缺失說明</td>
                    <td colspan="9">
                        @if ($defectsFlat->first()->description == null)
                            無
                        @else
                            {{-- array to string --}}
                            @foreach ($defectsFlat->first()->description as $description)
                                {{ $description }}
                            @endforeach
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="">備註</td>
                    <td colspan="9" align="">
                        {{ $defectsFlat->first()->memo }}
                        @if ($defectsFlat->first()->is_ignore)
                            <span style="color: red">（忽略扣分）</span>
                        @endif
                        @if ($defectsFlat->first()->is_not_reach_deduct_standard)
                            <span style="color: red">（未達扣分標準）</span>
                        @endif
                        @if ($defectsFlat->first()->is_suggestion)
                            <span style="color: red">（建議事項）</span>
                        @endif
                        @if ($defectsFlat->first()->is_repeat)
                            <span style="color: red">（重複缺失）</span>
                        @endif
                    </td>
                </tr>
            </table>
            @if ($defectsFlat->count() > 1)
                <div style="page-break-after:always"></div>
            @endif
        @endif

        @foreach ($defectsFlat->skip(1) as $item)
            <table border="1" width="100%" height="100%" style="padding: 2px;margin-top: 10px;">
                <tr>
                    <td colspan="12" align="center" style="background-color:bisque">
                        {{ $item->restaurantWorkspace->area }}</td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: center">
                        @isset($item->images[0])
                            <img src="data:image/png;base64,{{ $item->images[0] }}"
                                width="{{ count($item->images) >= 3 ? '80px' : '150px' }}" alt="test">
                        @endisset
                    </td>
                    <td colspan="6" style="text-align: center">
                        @isset($item->images[1])
                            <img src="data:image/png;base64,{{ $item->images[1] }}" alt="test"
                                width="{{ count($item->images) >= 3 ? '80px' : '150px' }}">
                        @endisset
                    </td>
                </tr>
                @isset($item->images[2])
                    <tr>
                        <td colspan="6" style="text-align: center">
                            @isset($item->images[2])
                                <img src="data:image/png;base64,{{ $item->images[2] }}" alt="test"
                                    width="{{ count($item->images) >= 3 ? '80px' : '150px' }}">
                            @endisset
                        </td>
                        <td colspan="6" style="text-align: center">
                            @isset($item->images[3])
                                <img src="data:image/png;base64,{{ $item->images[3] }}" alt="test"
                                    width="{{ count($item->images) >= 3 ? '80px' : '150px' }}">
                            @endisset
                        </td>
                    </tr>
                @endisset
                <tr>
                    <td colspan="3" align="">主項目</td>
                    <td colspan="9" align="">{{ $item->clearDefect->main_item }}</td>
                </tr>
                <tr>
                    <td colspan="3" align="">次項目</td>
                    <td colspan="9" align="">{{ $item->clearDefect->sub_item }}</td>
                </tr>
                <tr>
                    <td colspan="3" align="">數量</td>
                    <td colspan="9" align="">{{ $item->amount }}</td>
                </tr>
                <tr>
                    {{-- 實際扣分 --}}
                    <td colspan="3" align="">實際扣分</td>
                    <td colspan="9" align="">
                        @if ($item->is_ignore || $item->is_not_reach_deduct_standard || $item->is_suggestion || $item->is_repeat)
                            0
                        @else
                            {{ $item->amount * -2 }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="">缺失說明</td>
                    <td colspan="9">
                        @if ($item->description == null)
                            無
                        @else
                            {{-- array to string --}}
                            @foreach ($item->description as $description)
                                {{ $description }}
                            @endforeach
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="3" align="">備註</td>
                    <td colspan="9" align="">
                        {{ $item->memo }}
                        @if ($item->is_ignore)
                            <span style="color: red">（忽略扣分）</span>
                        @endif
                        @if ($item->is_not_reach_deduct_standard)
                            <span style="color: red">（未達扣分標準）</span>
                        @endif
                        @if ($item->is_suggestion)
                            <span style="color: red">（建議事項）</span>
                        @endif
                        @if ($item->is_repeat)
                            <span style="color: red">（重複缺失）</span>
                        @endif
                    </td>
                </tr>
            </table>
            @if ($loop->iteration % 2 == 0 && !$loop->last)
                <div style="page-break-after:always"></div>
            @endif
        @endforeach
    </div>

</body>

</html>
