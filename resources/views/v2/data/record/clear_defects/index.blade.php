<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        {{ $title }}
    </x-slot:pageTitle>

    <x-slot:headerFiles>
        <script src="{{ asset('js/xlsx.full.min.js') }}"></script>
        <style>
            .my-table td:not(:last-child),
            .my-table th:not(:last-child) {
                border-right: 2px dashed #ccc;
            }
        </style>
    </x-slot:headerFiles>

    <div id="app">
        <v-app v-cloak>
            <v-main class="grey lighten-4">
                <v-container>
                    <v-toolbar color="primary darken-2" dark dense>
                        <v-toolbar-title>{{ $title }}</v-toolbar-title>
                        <v-spacer></v-spacer>
                        {{-- 圖表連結 --}}
                        <v-btn :href="`{{ route('clear-defect-chart') }}?yearMonth=${month}`" target="_blank" icon>
                            <v-icon>mdi-chart-bar</v-icon>
                        </v-btn>
                    </v-toolbar>
                    <v-data-table class="elevation-1 my-table" :headers="headers" :items="defectRecords"
                        item-key="id" :search="search" :loading="loading" height="calc(100vh - 300px)"
                        fixed-header>
                        <template v-slot:top>
                            <v-row align="center" class="pa-2">
                                <v-col cols="12" sm="6">
                                    <v-menu transition="scale-transition" offset-y max-width="290px" min-width="auto">
                                        <template v-slot:activator="{ on, attrs }">
                                            <v-text-field v-model="month" label="月份" append-icon="mdi-calendar"
                                                readonly v-bind="attrs" v-on="on" class="mr-2"></v-text-field>
                                        </template>
                                        <v-date-picker v-model="month" type="month" scrollable :locale="locale"
                                            @input="getDefectRecords">
                                        </v-date-picker>
                                    </v-menu>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="search" append-icon="mdi-magnify"
                                        label="日期、品牌、分店、區站、巡檢員、任務、缺失" single-line></v-text-field>
                                </v-col>
                            </v-row>
                        </template>
                        <template v-slot:item.reason="{ item }">
                            <v-chip v-if="item.is_ignore" color="success" class="mr-2" small>忽略扣分</v-chip>
                            <v-chip v-if="item.is_suggestion" color="info" class="mr-2" small>建議</v-chip>
                            <v-chip v-if="item.is_repeat" color="warning" class="mr-2" small>重複</v-chip>
                            <v-chip v-if="item.is_not_reach_deduct_standard" color="error" class="mr-2"
                                small>未達扣分標準</v-chip>
                        </template>
                        <template v-slot:item.actions="{ item }">
                            <v-btn icon small @click="dialog = true; detail = item">
                                <v-icon>mdi-eye</v-icon>
                            </v-btn>
                        </template>
                    </v-data-table>
                </v-container>

                {{-- 詳細 --}}
                <v-dialog v-model="dialog" max-width="900px">
                    <v-card>
                        <v-card-title>
                            <span class="headline">詳細資訊</span>
                        </v-card-title>
                        <v-card-text>
                            <v-row>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="稽核日期" v-model="detail.task.task_date"
                                        readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="品牌" v-model="detail.restaurant_workspace.restaurant.brand"
                                        readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="分店" v-model="detail.restaurant_workspace.restaurant.shop"
                                        readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="區站" v-model="detail.restaurant_workspace.area"
                                        readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="巡檢員" v-model="detail.user.name" readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="任務" v-model="detail.task.category" readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="主項目" v-model="detail.clear_defect.main_item"
                                        readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="子項目" v-model="detail.clear_defect.sub_item"
                                        readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-select :items="detail.description" v-model="detail.description" attach chips
                                        label="缺失說明" multiple disabled></v-select>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="扣分" v-model="detail.deduct_point" readonly></v-text-field>
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field label="備註" v-model="detail.memo" readonly></v-text-field>
                                </v-col>
                                <v-col cols="12">
                                    <v-img v-if="detail.images" v-for="(image, index) in detail.images"
                                        :key="index" :src="`/storage/${image}`" contain></v-img>
                                </v-col>
                            </v-row>
                        </v-card-text>
                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="blue darken-1" text @click="dialog = false">關閉</v-btn>
                        </v-card-actions>
                    </v-card>
                </v-dialog>
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
                    search: null,
                    month: new Date().toISOString().substr(0, 7),
                    locale: "zh-TW",
                    defectRecords: [],
                    headers: [{
                            text: '日期',
                            align: 'start',
                            value: 'created_at'
                        },
                        {
                            text: '品牌',
                            value: 'restaurant_workspace.restaurant.brand'
                        },
                        {
                            text: '分店',
                            value: 'restaurant_workspace.restaurant.shop'
                        },
                        {
                            text: '區站',
                            value: 'restaurant_workspace.area'
                        },
                        {
                            text: '巡檢員',
                            value: 'user.name'
                        },
                        {
                            text: '任務',
                            value: 'task.category'
                        },
                        {
                            text: '缺失',
                            value: 'clear_defect.sub_item'
                        },
                        {
                            text: '數量',
                            value: 'amount'
                        },
                        {
                            text: '扣分',
                            value: 'deduct_point'
                        },
                        {
                            text: '不扣分原因',
                            value: 'reason'
                        },
                        {
                            text: '操作',
                            value: 'actions',
                            sortable: false
                        }
                    ],
                    detail: {
                        restaurant_workspace: {
                            area: '',
                            restaurant: {
                                brand: '',
                                shop: ''
                            }

                        },

                        user: {
                            name: ''
                        },
                        task: {
                            task_date: '',
                            category: ''
                        },
                        clear_defect: {

                        },
                        memo: '',
                        images: []
                    },
                    dialog: false,

                },
                methods: {
                    getDefectRecords() {
                        this.loading = true;
                        axios.get('/api/clear-defect-records', {
                            params: {
                                month: this.month
                            }
                        }).then(response => {
                            this.defectRecords = response.data.data;
                            // 假如忽略扣分或是建議或是重複或未達扣分標準或是改善，則deduct_point=0, 再*amount
                            this.defectRecords.forEach(record => {
                                if (record.is_ignore || record.is_suggestion || record.is_repeat ||
                                    record.is_not_reach_deduct_standard) {
                                    record.deduct_point = 0;
                                } else {
                                    record.deduct_point = record.clear_defect.deduct_point * record
                                        .amount;
                                }

                            });
                            this.loading = false;
                        }).catch(error => {
                            console.log(error);
                            this.loading = false;
                        });
                    },

                },

                mounted() {
                    this.getDefectRecords();
                },

                watch: {
                    month() {
                        this.getDefectRecords();
                    }
                }
            })
        </script>
    </x-slot:footerFiles>


</x-base-layout>
