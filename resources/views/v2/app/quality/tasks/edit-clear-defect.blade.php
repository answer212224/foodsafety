<x-base-layout :scrollspy="false">
    <x-slot:pageTitle>
        {{ $title }}
    </x-slot:pageTitle>

    <x-slot:headerFiles>

    </x-slot:headerFiles>


    <div id="app">
        <v-app v-cloak>
            <v-main class="grey lighten-4">
                <v-container>
                    <v-row class="d-flex justify-space-between align-center">
                        <v-col cols="6">
                            {{-- 跳轉到列表 --}}
                            <v-btn color="primary" text href="{{ route('v2.app.quality-tasks.index') }}">
                                <v-icon left>mdi-arrow-left</v-icon>
                                返回列表
                            </v-btn>
                        </v-col>

                        <v-col cols="6" class="text-right">
                            {{-- 跳轉到新增缺失 --}}
                            <v-btn color="primary" text
                                href="{{ route('v2.app.quality-tasks.clear-defect.create', $task->id) }}">
                                新增缺失
                                <v-icon right>mdi-arrow-right</v-icon>
                            </v-btn>
                        </v-col>

                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <v-card>
                                <v-card-title>
                                    <span class="headline">清檢分數</span>
                                </v-card-title>
                                <v-card-text>
                                    <span class="headline">@{{ totalInnerScore }}分</span>
                                </v-card-text>
                            </v-card>
                        </v-col>
                    </v-row>

                    <v-row v-if="loading">
                        <v-col cols="12" sm="4" v-for="n in 6" :key="n">
                            <template>
                                <v-sheet class="pa-3">
                                    <v-skeleton-loader class="mx-auto" type="card"></v-skeleton-loader>
                                </v-sheet>
                            </template>
                        </v-col>
                    </v-row>

                    <v-row>
                        <v-col cols="12">
                            <v-tabs color="deep-purple accent-4" v-model="tab" show-arrows>
                                <v-tab v-for="tab in tabs" :key="tab"
                                    class="elevation-3">@{{ tab }}</v-tab>
                                <v-tab-item v-for="tab in tabs" :key="tab" class="grey lighten-4">
                                    <v-row>
                                        <v-col cols="12" sm="4" v-for="taskDefect in taskDefects[tab]"
                                            :key="taskDefects.id">
                                            <v-card class="my-3">
                                                <v-carousel height="200">
                                                    <v-carousel-item v-for="(item,i) in taskDefect.images_url"
                                                        :key="i" :href="item" target="_blank">
                                                        <v-img :src="item" :lazy-src="item"
                                                            aspect-ratio="1"></v-img>
                                                    </v-carousel-item>
                                                </v-carousel>

                                                <v-card-text>
                                                    <v-row>
                                                        <v-col cols="12">
                                                            <v-chip-group>
                                                                {{-- 扣分 --}}
                                                                <v-chip color="warning" text dark small label
                                                                    v-if="!(taskDefect.is_ignore||taskDefect.is_not_reach_deduct_standard||taskDefect.is_suggestion||taskDefect.is_repeat)">
                                                                    @{{ taskDefect.amount * -2 }}分
                                                                </v-chip>
                                                                <v-chip color="red" text dark small label
                                                                    v-if="taskDefect.is_ignore">忽略扣分</v-chip>
                                                                <v-chip color="red" text dark small label
                                                                    v-if="taskDefect.is_not_reach_deduct_standard">未達扣分標準</v-chip>
                                                                <v-chip color="red" text dark small label
                                                                    v-if="taskDefect.is_suggestion">建議事項</v-chip>
                                                                <v-chip color="red" text dark small label
                                                                    v-if="taskDefect.is_repeat">重複缺失</v-chip>

                                                            </v-chip-group>
                                                        </v-col>
                                                        {{-- 主項目 --}}
                                                        <v-col cols="12">
                                                            <v-text-field label="主項目"
                                                                v-model="taskDefect.clear_defect.main_item" readonly
                                                                dense></v-text-field>
                                                        </v-col>
                                                        {{-- 次項目 --}}
                                                        <v-col cols="12">
                                                            <v-text-field label="次項目"
                                                                v-model="taskDefect.clear_defect.sub_item" readonly
                                                                dense></v-text-field>
                                                        </v-col>
                                                        {{-- 數量 --}}
                                                        <v-col cols="12">
                                                            <v-text-field label="數量" v-model="taskDefect.amount"
                                                                readonly dense></v-text-field>
                                                        </v-col>
                                                        {{-- 缺失說明 --}}
                                                        <v-col cols="12">
                                                            <v-chip-group>
                                                                <v-chip color="primary" text dark small label
                                                                    v-for="description in taskDefect.description"
                                                                    :key="description">
                                                                    @{{ description }}
                                                                </v-chip>
                                                            </v-chip-group>
                                                        </v-col>
                                                        <v-col cols="12">
                                                            <v-textarea label="備註" v-model="taskDefect.memo"
                                                                readonly rows="2"></v-textarea>
                                                        </v-col>
                                                        {{-- 稽核員 --}}
                                                        <v-col cols="12">
                                                            <v-text-field label="稽核員" v-model="taskDefect.user.name"
                                                                readonly dense></v-text-field>
                                                        </v-col>
                                                        {{-- 稽核日期 --}}
                                                        <v-col cols="12">
                                                            <v-text-field label="稽核時間" v-model="taskDefect.created_at"
                                                                readonly dense></v-text-field>
                                                        </v-col>

                                                    </v-row>

                                                </v-card-text>
                                                <v-card-actions>
                                                    <v-spacer></v-spacer>
                                                    <v-btn color="primary" text
                                                        @click="openDialog(taskDefect)">編輯</v-btn>
                                                    <v-btn color="red" text
                                                        @click="deleteItem(taskDefect)">刪除</v-btn>
                                                </v-card-actions>
                                            </v-card>
                                        </v-col>
                                    </v-row>
                                </v-tab-item>

                            </v-tabs>
                        </v-col>
                    </v-row>

                    <v-divider></v-divider>

                </v-container>

                <v-dialog v-model="dialog" max-width="500px">
                    <v-card>
                        <v-card-title>
                            <span class="headline">編輯缺失</span>
                        </v-card-title>
                        <v-card-text>
                            <v-container>
                                <v-row>
                                    {{-- 區站 --}}
                                    <v-col cols="12">
                                        <v-select label="區站" v-model="editedItem.restaurant_workspace_id"
                                            :items="workSpaces" item-text="area" item-value="id" dense></v-select>
                                    </v-col>
                                    <v-col cols="12">
                                        {{-- 主項目 --}}
                                        <v-select label="主項目" v-model="editedItem.clear_defect.main_item"
                                            :items="main_defects" dense></v-select>
                                    </v-col>
                                    <v-col cols="12">
                                        {{-- 次項目 --}}
                                        <v-select label="次項目" v-model="editedItem.quality_clear_defect_id"
                                            :items="activeDefects[editedItem.clear_defect.main_item]"
                                            item-text="sub_item" item-value="id" dense></v-select>
                                    </v-col>

                                    <v-col cols="12">
                                        {{-- 數量 --}}
                                        <v-text-field v-model="editedItem.amount" label="數量" type="number"
                                            :rules="[v => v >= 0 || '數量不得為負']">
                                            <v-icon slot="append" color="green" @click="editedItem.amount++">
                                                mdi-plus
                                            </v-icon>
                                            <v-icon slot="prepend" color="red" @click="editedItem.amount--">
                                                mdi-minus
                                            </v-icon>
                                        </v-text-field>
                                    </v-col>

                                    {{-- 缺失說明 --}}
                                    <v-col cols="12">
                                        <v-combobox label="缺失說明" v-model="editedItem.description"
                                            :items="items" dense multiple chips></v-combobox>
                                    </v-col>

                                    {{-- 忽略扣分 未達扣分標準 建議事項 --}}
                                    <v-col cols="6">
                                        <v-checkbox v-model="editedItem.is_ignore" label="忽略扣分"
                                            color="red"></v-checkbox>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-checkbox v-model="editedItem.is_not_reach_deduct_standard" label="未達扣分標準"
                                            color="red"></v-checkbox>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-checkbox v-model="editedItem.is_suggestion" label="建議事項"
                                            color="red"></v-checkbox>
                                    </v-col>
                                    <v-col cols="6">
                                        <v-checkbox v-model="editedItem.is_repeat" label="重複缺失"
                                            color="red"></v-checkbox>
                                    </v-col>

                                    <v-col cols="12">
                                        <v-textarea label="備註" v-model="editedItem.memo"
                                            rows="2"></v-textarea>
                                    </v-col>
                                </v-row>
                            </v-container>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="blue darken-1" text @click="close">取消</v-btn>
                            <v-btn color="blue darken-1" text @click="save"
                                :disabled="!editedItem.quality_clear_defect_id || (editedItem.amount < 1)">
                                儲存
                            </v-btn>
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
                    // 任務的缺失
                    taskDefects: null,
                    tab: null,
                    tabs: [],
                    loading: false,
                    dialog: false,
                    workSpaces: [],
                    items: [
                        '積垢不潔',
                        '積塵',
                        '留有食渣',
                        '留有病媒屍體',
                        '留有垃圾'
                    ],

                    editedItem: {
                        clear_defect: {

                        },
                    },
                    activeDefects: [],
                    main_defects: [],
                    totalInnerScore: 0,
                    totalOuterScore: 0,

                },

                methods: {
                    getDefects() {
                        this.loading = true;
                        axios.get(`/api/quality-tasks/{{ $task->id }}/clear-defects`)
                            .then((res) => {
                                this.taskDefects = res.data.data;
                                this.tabs = Object.keys(this.taskDefects);
                            })
                            .catch((err) => {
                                alert(err.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },

                    getActiveDefects() {
                        axios.get(`/api/quality-clear-defects/active`)
                            .then((res) => {
                                this.activeDefects = res.data.data;
                                // 將缺失條文的key值轉成陣列
                                this.main_defects = Object.keys(this.activeDefects);
                            })
                            .catch((err) => {
                                alert(err.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },

                    getRestaurantsWorkSpaces() {
                        axios.get(`/api/restaurants/work-spaces`, {
                                params: {
                                    restaurant_id: {{ $task->restaurant_id }}
                                }
                            })
                            .then((res) => {
                                this.workSpaces = res.data.data.restaurant_workspaces;
                            })
                            .catch((err) => {
                                alert(err.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },


                    getTaskScore() {
                        this.loading = true;
                        axios.get(`/api/quality-tasks/{{ $task->id }}/clear-defect/score`).then((res) => {
                                this.totalInnerScore = res.data.data.inner_score;
                                this.totalOuterScore = res.data.data.outer_score;
                            })
                            .catch((err) => {
                                alert(err.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });

                    },

                    openDialog(taskDefect) {
                        this.editedItem = structuredClone(taskDefect)
                        this.dialog = true;
                    },

                    close() {
                        this.loading = true;
                        this.dialog = false;
                        this.getDefects();
                        this.getTaskScore();
                    },
                    save() {
                        this.dialog = false;
                        this.loading = true;
                        axios.put(`/api/quality-tasks/clear-defects/${this.editedItem.id}`, {
                                restaurant_workspace_id: this.editedItem.restaurant_workspace_id,
                                quality_clear_defect_id: this.editedItem.quality_clear_defect_id,
                                amount: this.editedItem.amount,
                                description: this.editedItem.description,
                                is_ignore: this.editedItem.is_ignore,
                                is_not_reach_deduct_standard: this.editedItem.is_not_reach_deduct_standard,
                                is_suggestion: this.editedItem.is_suggestion,
                                memo: this.editedItem.memo,
                            })
                            .then((res) => {
                                if (res.data.status == 'success') {
                                    this.getDefects();
                                    this.getTaskScore();
                                } else {
                                    alert(res.data.message);
                                }

                            })
                            .catch((err) => {
                                alert(err.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },

                    // 刪除
                    deleteItem(taskDefect) {
                        const confirm = window.confirm('確定要刪除嗎?');
                        if (!confirm) {
                            return;
                        }
                        this.loading = true;
                        axios.delete(`/api/quality-tasks/clear-defects/${taskDefect.id}`)
                            .then((res) => {
                                if (res.data.status == 'success') {
                                    this.getDefects();
                                    this.getTaskScore();
                                    alert('刪除成功');
                                } else {
                                    alert('刪除失敗');
                                }
                            })
                            .catch((err) => {
                                alert(err.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },
                },

                mounted() {
                    this.getDefects();
                    this.getActiveDefects();
                    this.getTaskScore();
                    this.getRestaurantsWorkSpaces();
                },
            });
        </script>
    </x-slot:footerFiles>
</x-base-layout>
