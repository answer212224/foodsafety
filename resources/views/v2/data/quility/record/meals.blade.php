<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        {{ $title }}
    </x-slot:pageTitle>

    <x-slot:headerFiles>
        <style>
            .my-table td:not(:last-child),
            .my-table th:not(:last-child) {
                border-right: 2px dashed #ccc;
            }
        </style>
        <script src="{{ asset('js/xlsx.full.min.js') }}"></script>
    </x-slot:headerFiles>

    <div id="app">
        <v-app v-cloak>
            <v-main class="grey lighten-4">
                <v-container>

                    <v-toolbar color="primary darken-2" dark dense>
                        <v-toolbar-title>{{ $title }}</v-toolbar-title>
                    </v-toolbar>

                    <v-data-table class="elevation-1 my-table" :headers="headers" :items="mealRecords"
                        :loading="loading" sort-by="task_date" height="calc(100vh - 300px)" fixed-header>
                        <template v-slot:top>
                            <v-row class="d-flex justify-end" align="center">
                                <v-col cols="3">
                                    {{-- 日期區間 --}}
                                    <v-menu v-model="menu" :close-on-content-click="false" transition="scale-transition"
                                        offset-y>
                                        <template v-slot:activator="{ on }">
                                            <v-text-field v-model="dates" label="日期區間" prepend-icon="mdi-calendar"
                                                readonly v-on="on"></v-text-field>
                                        </template>
                                        <v-date-picker v-model="dates" range locale="zh-TW"></v-date-picker>
                                    </v-menu>
                                </v-col>
                                <v-col cols="1">
                                    <v-btn icon @click="exportExcel" color="success">
                                        <v-icon>mdi-file-excel</v-icon>
                                    </v-btn>
                                </v-col>
                            </v-row>
                        </template>
                        <template v-slot:item.is_taken="{ item }">
                            <v-chip v-if="item.is_taken" color="green" dark small>已取</v-chip>
                            <v-chip v-else color="red" dark small>未取</v-chip>
                        </template>
                    </v-data-table>

                </v-container>
            </v-main>
        </v-app>
    </div>

    <x-slot:footerFiles>
        <script>
            new Vue({
                el: '#app',
                vuetify: new Vuetify(),
                data: {
                    loading: false,
                    dates: [],
                    mealRecords: [],
                    menu: false,
                    headers: [{
                            text: '月份',
                            align: 'start',
                            value: 'meal_effective_month'
                        },
                        {
                            text: '日期',
                            value: 'task_date'
                        },
                        {
                            text: '分店代碼',
                            value: 'restaurant_sid'
                        },
                        {
                            text: '品牌',
                            value: 'restaurant_brand'
                        },
                        {
                            text: '店別',
                            value: 'restaurant_shop'
                        },
                        {
                            text: '類別',
                            value: 'meal_category'
                        },
                        {
                            text: '廚別',
                            value: 'meal_chef'
                        },
                        {
                            text: '區站',
                            value: 'meal_workspace'
                        },
                        {
                            text: '編號',
                            value: 'meal_qno'
                        },
                        {
                            text: '名稱',
                            value: 'meal_name'
                        },
                        {
                            text: '備忘錄',
                            value: 'meal_note'
                        },
                        {
                            text: '檢項',
                            value: 'meal_item'
                        },
                        {
                            text: '檢驗項目',
                            value: 'meal_items'
                        },
                        {
                            text: '是否已取',
                            value: 'is_taken'
                        },
                        {
                            text: '備註',
                            value: 'memo'
                        },
                    ]
                },
                methods: {
                    getMealRecords() {
                        this.loading = true
                        axios.get('/api/quality-meal-records', {
                                params: {
                                    dates: this.dates
                                }
                            })
                            .then(response => {
                                this.mealRecords = response.data.data
                            })
                            .catch(error => {
                                alert(error.response.data.message)
                            })
                            .finally(() => {
                                this.loading = false
                            })
                    },

                    exportExcel() {
                        var wb = XLSX.utils.book_new();
                        var ws = XLSX.utils.json_to_sheet(
                            this.mealRecords.map(({
                                meal_effective_month,
                                task_date,
                                restaurant_sid,
                                restaurant_brand,
                                restaurant_shop,
                                meal_category,
                                meal_chef,
                                meal_workspace,
                                meal_qno,
                                meal_name,
                                meal_note,
                                meal_item,
                                meal_items,
                                is_taken,
                                memo
                            }) => ({
                                '月份': meal_effective_month,
                                '日期': task_date,
                                '分店代碼': restaurant_sid,
                                '品牌': restaurant_brand,
                                '店別': restaurant_shop,
                                '類別': meal_category,
                                '廚別': meal_chef,
                                '區站': meal_workspace,
                                '編號': meal_qno,
                                '名稱': meal_name,
                                '備忘錄': meal_note,
                                '檢項': meal_item,
                                '檢驗項目': meal_items,
                                '是否已取': is_taken ? '已取' : '未取',
                                '備註': memo
                            }))
                        );
                        XLSX.utils.book_append_sheet(wb, ws, "Sheet1");
                        XLSX.writeFile(wb, this.month + '食材_成品採樣記錄.xlsx');
                    },

                },

                mounted() {
                    // 取得當月第一天與最後一天 轉成日期字串
                    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toLocaleDateString(
                        'fr-CA');

                    const lastDay = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toLocaleDateString(
                        'fr-CA');

                    this.dates = [firstDay, lastDay]
                    this.getMealRecords()
                },

                watch: {
                    dates() {
                        // dates 需要有兩個日期才能查詢
                        if (this.dates.length === 2) {
                            this.getMealRecords()
                        }

                    }
                }
            });
        </script>
    </x-slot:footerFiles>


</x-base-layout>
