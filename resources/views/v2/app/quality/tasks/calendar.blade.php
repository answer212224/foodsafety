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
                    <v-row class="fill-height">
                        <v-col cols="12" sm="12" md="6">
                            <v-select v-model="type" :items="['month', 'day', '4day']" label="顯示模式"
                                :loading="loading">
                        </v-col>

                        <v-col cols="12">
                            <v-sheet height="64">
                                <v-toolbar flat>
                                    <v-btn fab text small color="grey darken-2" @click="prev">
                                        <v-icon small>
                                            mdi-chevron-left
                                        </v-icon>
                                    </v-btn>
                                    <v-btn fab text small color="grey darken-2" @click="next">
                                        <v-icon small>
                                            mdi-chevron-right
                                        </v-icon>
                                    </v-btn>
                                    <v-toolbar-title v-if="$refs.calendar">
                                        @{{ $refs.calendar.title }}
                                    </v-toolbar-title>
                                    <v-spacer></v-spacer>
                                    <v-btn fab small color="primary" @click="exportExcel" class="mr-2">
                                        <v-icon small>
                                            mdi-file-excel
                                        </v-icon>
                                    </v-btn>
                                    <v-btn fab small color="primary" @click="showNotAssign" class="mr-2">
                                        <v-icon small>
                                            mdi-eye
                                        </v-icon>
                                    </v-btn>
                                    @can('import-data')
                                        <v-btn fab small color="primary" @click="importDialog = true;importFile=null"
                                            class="mr-2">
                                            <v-icon small>
                                                mdi-calendar-import
                                            </v-icon>
                                        </v-btn>
                                    @endcan
                                    @can('create-task')
                                        <v-btn fab small color="primary" @click="openDialog">
                                            <v-icon small>
                                                mdi-plus
                                            </v-icon>
                                        </v-btn>
                                    @endcan
                                </v-toolbar>
                            </v-sheet>
                            <v-sheet height="auto">
                                <v-calendar ref="calendar" v-model="focus" color="primary" :events="events"
                                    :type="type" @click:event="showEvent" event-overlap-mode="stack"
                                    :event-more="false" locale="zh-tw" :event-overlap-threshold="1"
                                    :interval-count="0">
                                </v-calendar>
                                <v-menu v-model="selectedOpen" :activator="selectedElement" offset-y>
                                    <v-card>
                                        <v-toolbar :color="selectedEvent.color" dark>
                                            @can('update-task')
                                                <v-btn icon dark @click="editEvent(selectedEvent);">
                                                    <v-icon>mdi-pencil</v-icon>
                                                </v-btn>
                                            @endcan
                                            <v-toolbar-title>
                                                @{{ selectedEvent.name }}
                                            </v-toolbar-title>

                                        </v-toolbar>
                                        <v-card-text>
                                            <v-row>
                                                {{-- 狀態 --}}
                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2" small>mdi-check</v-icon>
                                                    <span v-if="selectedEvent.status==='pending'"
                                                        class="text--primary">未稽核</span>
                                                    <span v-if="selectedEvent.status==='processing'"
                                                        class="text--primary">稽核中</span>
                                                    <span v-if="selectedEvent.status==='pending_approval'"
                                                        class="text--primary">待審核</span>
                                                    <span v-if="selectedEvent.status==='completed'"
                                                        class="text--primary">已完成</span>
                                                </v-col>
                                                {{-- 類別 --}}
                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2" small>mdi-tag</v-icon>
                                                    <span class="text--primary">
                                                        @{{ selectedEvent.category }}</span>
                                                </v-col>
                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2" small>mdi-calendar</v-icon>
                                                    <span class="text--primary">
                                                        @{{ selectedEvent.date }} @{{ selectedEvent.time }}</span>

                                                </v-col>
                                                {{-- 地點 --}}
                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2" small>mdi-map-marker</v-icon>
                                                    <span class="text--primary">
                                                        @{{ selectedEvent.brand }} @{{ selectedEvent.shop }}</span>
                                                </v-col>
                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2" small>mdi-account</v-icon>
                                                    <span class="text--primary" v-for="user in selectedEvent.users">
                                                        @{{ user.name }}</span>

                                                </v-col>
                                                <v-col cols="12">
                                                    <v-icon left color="teal darken-2" small>mdi-food</v-icon>
                                                    <v-chip v-for="meal in selectedEvent.meals" class="ma-1"
                                                        :key="meal.id" color="blue-grey darken-3" dark small>
                                                        @{{ meal.name }}
                                                        <v-icon v-if="meal.pivot && meal.pivot.is_taken" right small
                                                            color="success">mdi-check</v-icon>
                                                    </v-chip>
                                                </v-col>


                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2"
                                                        small>mdi-timer-play-outline</v-icon>
                                                    <span class="text--primary">
                                                        @{{ selectedEvent.start_at }}</span>

                                                </v-col>
                                                <v-col cols="12" sm="12" md="6">
                                                    <v-icon left color="teal darken-2"
                                                        small>mdi-timer-off-outline</v-icon>
                                                    <span class="text--primary">
                                                        @{{ selectedEvent.end_at }}</span>
                                                </v-col>
                                            </v-row>
                                        </v-card-text>
                                        <v-divider></v-divider>
                                        <v-card-actions>
                                            <v-btn text color="blue darken-1"
                                                v-show="selectedEvent.category!=='食材/成品採樣'"
                                                :href="'/v2/app/quality-task/' + selectedEvent.id + '/report'"
                                                target="_blank">
                                                報告
                                            </v-btn>


                                            @can('update-task')
                                                <v-btn v-show="selectedEvent.category=='食安巡檢'" text color="blue darken-1"
                                                    :href="'/v2/app/quality-task/' + selectedEvent.id + '/defect/edit'">
                                                    編輯缺失
                                                </v-btn>
                                                <v-btn v-show="selectedEvent.category=='清潔檢查'" text color="blue darken-1"
                                                    :href="'/v2/app/quality-task/' + selectedEvent.id + '/clear-defect/edit'">
                                                    編輯缺失
                                                </v-btn>
                                            @endcan
                                            <v-spacer></v-spacer>
                                            @can('delete-task')
                                                <v-btn text color="red darken-1" @click="remove(selectedEvent.id)">
                                                    刪除
                                                </v-btn>
                                            @endcan
                                        </v-card-actions>
                                    </v-card>
                                </v-menu>
                            </v-sheet>
                        </v-col>
                    </v-row>


                    <v-dialog v-model="dialog" max-width="500px">
                        <v-card>
                            <v-card-title>
                                <span class="headline" v-if="editedIndex === -1">新增任務</span>
                                <span class="headline" v-else>編輯任務</span>
                            </v-card-title>

                            <v-card-text>

                                {{-- 類別 --}}
                                <v-row>
                                    <v-col cols="12" sm="12" md="12">
                                        <v-select v-model="editedItem.category" :items="Object.keys(categories)"
                                            label="類別" :readonly="editedIndex > -1">
                                        </v-select>
                                    </v-col>
                                </v-row>

                                <v-row>
                                    <v-col cols="12" sm="12" md="6">
                                        <v-menu ref="selectDateOpen" v-model="selectDateOpen"
                                            transition="scale-transition" offset-y min-width="auto">
                                            <template v-slot:activator="{ on, attrs }">
                                                <v-text-field v-model="editedItem.date" label="日期"
                                                    prepend-icon="mdi-calendar" readonly v-bind="attrs"
                                                    v-on="on">
                                                </v-text-field>
                                            </template>
                                            <v-date-picker v-model="editedItem.date" scrollable locale="zh-tw">
                                            </v-date-picker>
                                        </v-menu>
                                    </v-col>
                                    <v-col cols="12" sm="12" md="6">
                                        <v-menu ref="selectTimeOpen" v-model="selectTimeOpen"
                                            transition="scale-transition" offset-y min-width="auto"
                                            :close-on-content-click="false">
                                            <template v-slot:activator="{ on, attrs }">
                                                <v-text-field v-model="editedItem.time" label="時間"
                                                    prepend-icon="mdi-clock-time-four-outline" readonly v-bind="attrs"
                                                    v-on="on">
                                                </v-text-field>
                                            </template>
                                            <v-time-picker v-model="editedItem.time" :allowed-minutes="allowedStep"
                                                locale="zh-tw">
                                            </v-time-picker>
                                        </v-menu>
                                    </v-col>

                                    <v-col cols="12" sm="12" md="12">
                                        <v-select v-model="editedItem.users" :items="users" multiple chips
                                            label="同仁" item-text="name" item-value="id" return-object>
                                        </v-select>
                                    </v-col>

                                    <v-col cols="12" sm="12" md="12">
                                        <v-select v-model="editedItem.brand" :items="Object.keys(restaurants)"
                                            label="品牌">
                                        </v-select>
                                    </v-col>

                                    <v-col cols="12" sm="12" md="12">
                                        <v-select v-model="editedItem.restaurant"
                                            :items="restaurants[editedItem.brand]" label="分店" item-text="shop"
                                            item-value="sid" return-object>
                                        </v-select>
                                    </v-col>

                                    <v-col cols="12" sm="12" md="12"
                                        v-if="editedItem.restaurant">
                                        <v-select v-model="editedItem.meals" :items="meals" multiple chips
                                            label="食材/成品" return-object clearable item-text="name" item-value="id">
                                            :loading="loading">
                                        </v-select>
                                    </v-col>



                                </v-row>

                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="blue darken-1" text @click="save"
                                    :disabled="!editedItem.category || !editedItem.date || !editedItem
                                        .time || !editedItem.users ||
                                        !editedItem.restaurant">
                                    儲存
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>

                    <v-dialog v-model="notAssignDialog" max-width="800px">
                        <v-card>
                            <v-card-title>
                                {{-- 2024年01月 --}}
                                <span class="headline mx-auto">@{{ focus.split('-')[0] }}年@{{ focus.split('-')[1] }}月
                                    共@{{ notAssign.length }}間分店未排稽核任務
                                </span>
                            </v-card-title>

                            <v-card-text>


                                <v-container>
                                    <v-row>
                                        <v-col cols="4" v-for="item in notAssign" :key="item.id">
                                            <v-card hover>
                                                <v-card-text>
                                                    <v-icon left>mdi-store</v-icon>
                                                    @{{ item.sid }}<v-spacer></v-spacer>
                                                    @{{ item.brand }}
                                                    @{{ item.shop }}
                                                </v-card-text>
                                            </v-card>
                                        </v-col>
                                    </v-row>
                                </v-container>


                            </v-card-text>
                        </v-card>
                    </v-dialog>

                    <v-dialog v-model="importDialog" max-width="500px">
                        <v-card>
                            <v-card-title>
                                <span class="headline">匯入任務</span>
                                <v-spacer></v-spacer>
                                <a href="{{ asset('storage/quality_task_import.xlsx') }}" download>範例下載</a>
                            </v-card-title>

                            <v-card-text>
                                <v-row>
                                    <v-col cols="12" sm="12" md="12">
                                        <v-file-input v-model="importFile" label="選擇檔案" dense
                                            :loading="loading" accept=".xlsx">
                                        </v-file-input>
                                    </v-col>
                                </v-row>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="blue darken-1" text @click="importDialog = false">取消</v-btn>
                                <v-btn color="blue darken-1" text @click="importTask" :disabled="!importFile">
                                    匯入
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>


                </v-container>
            </v-main>
        </v-app>
    </div>

    <x-slot:footerFiles>
        <script src="{{ asset('js/exceljs.js') }}"></script>

        <script>
            new Vue({
                el: '#app',
                vuetify: new Vuetify(),

                data: () => ({
                    focus: new Date().toISOString().substr(0, 10),
                    selectedEvent: {},
                    selectedElement: null,
                    selectedOpen: false,
                    events: [],
                    type: 'month',
                    loading: false,
                    dialog: false,
                    categories: {
                        '食安巡檢': 'primary',
                        '清潔檢查': 'warning',
                        '食材/成品採樣': 'success',
                        '原料驗收查核': 'secondary',
                        '製程巡檢': 'error',
                    },
                    editedItem: {},
                    selectDateOpen: false,
                    selectTimeOpen: false,
                    users: [],
                    restaurants: [],
                    meals: [],
                    editedIndex: -1,
                    notAssignDialog: false,
                    notAssign: [],
                    importDialog: false,
                    importFile: null,
                    loading: false,
                }),

                methods: {
                    prev() {
                        this.$refs.calendar.prev()
                    },
                    next() {
                        this.$refs.calendar.next()
                    },

                    showNotAssign() {
                        this.loading = true
                        axios.get('/api/restaurants/unassigned', {
                                params: {
                                    date: this.focus,
                                    is_quality: 1,
                                }
                            })
                            .then((res) => {
                                this.notAssignDialog = true
                                this.notAssign = res.data.data

                            })
                            .catch((err) => {
                                console.log(err)
                            })
                            .finally(() => {
                                this.loading = false
                            })
                    },

                    showEvent({
                        nativeEvent,
                        event
                    }) {
                        const open = () => {
                            this.selectedEvent = event
                            this.selectedElement = nativeEvent.target
                            requestAnimationFrame(() => requestAnimationFrame(() => this.selectedOpen =
                                true))
                        }

                        if (this.selectedOpen) {
                            this.selectedOpen = false
                            requestAnimationFrame(() => requestAnimationFrame(() => open()))
                        } else {
                            open()
                        }

                        nativeEvent.stopPropagation()
                    },

                    updateRange() {
                        this.events = []
                        this.loading = true
                        // 取得任務資料
                        axios.get('/api/quality-tasks', {
                                params: {
                                    year: this.focus.split('-')[0],
                                    month: this.focus.split('-')[1],
                                }
                            })
                            .then((res) => {
                                res.data.data.forEach((item) => {
                                    this.events.push({
                                        id: item.id,
                                        name: item.restaurant.brand + item
                                            .restaurant.shop + item.task_date.split(' ')[1] + item
                                            .users.map(
                                                user => user.name).join(','),
                                        brand: item.restaurant.brand_code,
                                        brand_name: item.restaurant.brand,
                                        shop: item.restaurant.shop,
                                        users: item.users,
                                        meals: item.meals,
                                        start: item.task_date.split(' ')[0],
                                        end_at: item.end_at,
                                        start_at: item.start_at,
                                        color: this.categories[item.category],
                                        date: item.task_date.split(' ')[0],
                                        time: item.task_date.split(' ')[1],
                                        category: item.category,
                                        restaurant: item.restaurant,
                                        status: item.status,
                                    })
                                })

                            })
                            .catch((err) => {
                                alert(err.response.data.message)
                            })
                            .finally(() => {
                                this.loading = false
                            })
                    },

                    editEvent(event) {
                        this.getExecuteTaskUsers()
                        this.getRestaurants()
                        this.editedIndex = this.events.indexOf(event)
                        this.selectedOpen = false
                        this.dialog = true
                        this.editedItem = structuredClone(event)
                    },

                    allowedStep: m => m % 30 === 0,

                    getExecuteTaskUsers() {
                        this.loading = true
                        axios.get('/api/users/execute-quality-task')
                            .then((res) => {
                                this.users = res.data.data
                            })
                            .catch((err) => {
                                alert(err.response.data.message)
                            })
                            .finally(() => {
                                this.loading = false
                            })
                    },

                    getRestaurants() {
                        this.loading = true
                        axios.get('/api/restaurants', {
                                params: {
                                    is_group_by_brand_code: 1,
                                    is_quality: 1,
                                    status: 1,
                                }
                            })
                            .then((res) => {
                                this.restaurants = res.data.data

                            })
                            .catch((err) => {
                                alert(err.response.data.message)
                            })
                            .finally(() => {
                                this.loading = false
                            })
                    },

                    getMeals() {
                        loading = true
                        axios.get('/api/restaurants/quality-meals', {
                                params: {
                                    date: this.editedItem.date,
                                    sid: this.editedItem.restaurant.sid,
                                    brand_code: this.editedItem.restaurant.brand_code,
                                }
                            })
                            .then((res) => {
                                this.meals = res.data.data
                                if (this.editedIndex === -1) {
                                    this.editedItem.meals = this.meals
                                } else {
                                    this.editedItem.meals = this.editedItem.meals.filter((item) => {
                                        return this.meals.some((meal) => {
                                            return meal.id === item.id
                                        })
                                    })
                                }
                                this.loading = false
                            })
                            .catch((err) => {
                                alert(err.response.data.message)
                            })
                            .finally(() => {
                                this.loading = false
                            })
                    },



                    save() {
                        if (this.editedIndex > -1) {
                            axios.put('/api/quality-tasks/' + this.editedItem.id, {
                                    date: this.editedItem.date,
                                    time: this.editedItem.time,
                                    users: this.editedItem.users,
                                    restaurant: this.editedItem.restaurant,
                                    meals: this.editedItem.meals,
                                    category: this.editedItem.category,
                                })
                                .then((res) => {
                                    this.updateRange()
                                })
                                .catch((err) => {
                                    console.log(err)
                                })

                        } else {
                            axios.post('/api/quality-tasks', {
                                    date: this.editedItem.date,
                                    time: this.editedItem.time,
                                    users: this.editedItem.users,
                                    restaurant: this.editedItem.restaurant,
                                    meals: this.editedItem.meals,
                                    category: this.editedItem.category,
                                })
                                .then((res) => {
                                    this.updateRange()
                                })
                                .catch((err) => {
                                    console.log(err)
                                })
                        }
                        this.dialog = false
                        this.editedItem = {}
                    },

                    openDialog(item) {
                        this.getExecuteTaskUsers()
                        this.getRestaurants()
                        this.dialog = true
                        this.editedIndex = -1
                        this.editedItem = {
                            date: this.focus,
                            time: '09:00',
                            users: [],
                            restaurant: null,
                            meals: [],
                            category: '食安巡檢',
                        }

                    },

                    remove(id) {
                        const confirm = window.confirm('確定刪除?')
                        if (!confirm) {
                            return
                        }
                        axios.delete('/api/quality-tasks/' + id)
                            .then((res) => {
                                this.updateRange()
                            })
                            .catch((err) => {
                                alert(err.response.data.message)
                            })
                            .finally(() => {
                                this.selectedOpen = false
                            })
                    },

                    importTask() {
                        this.loading = true
                        const formData = new FormData()
                        formData.append('file', this.importFile)

                        axios.post('/api/quality-tasks/import', formData, {
                                headers: {
                                    'Content-Type': 'multipart/form-data'
                                }
                            })
                            .then((res) => {
                                if (res.data.status == 'success') {
                                    alert('匯入成功')
                                } else {
                                    alert(res.data.message)
                                }
                            })
                            .catch((err) => {
                                alert(err.response.data.message)
                            }).finally(() => {
                                this.importDialog = false
                                this.updateRange()
                                this.loading = false
                            })
                    },

                    exportExcel() {
                        const workbook = new ExcelJS.Workbook();
                        const worksheet = workbook.addWorksheet('品保行程');
                        worksheet.columns = [{
                                header: '日期',
                                key: 'date',
                                width: 15
                            },
                            {
                                header: '時間',
                                key: 'time',
                                width: 15
                            },
                            {
                                header: '品牌',
                                key: 'brand_name',
                                width: 15
                            },
                            {
                                header: '分店',
                                key: 'shop',
                                width: 15
                            },
                            {
                                header: '類別',
                                key: 'category',
                                width: 15
                            },
                            {
                                header: '同仁',
                                key: 'users',
                                width: 30
                            },
                            {
                                header: '食材/成品',
                                key: 'meals',
                                width: 30
                            },
                            {
                                header: '開始時間',
                                key: 'start_at',
                                width: 20
                            },
                            {
                                header: '結束時間',
                                key: 'end_at',
                                width: 20
                            },
                            {
                                header: '狀態',
                                key: 'status',
                                width: 15
                            },
                        ];

                        this.events.forEach((item) => {
                            worksheet.addRow({
                                date: item.date,
                                time: item.time,
                                brand_name: item.brand_name,
                                shop: item.shop,
                                category: item.category,
                                users: item.users.map(user => user.name).join(','),
                                meals: item.meals.map(meal => meal.name).join(','),
                                start_at: item.start_at,
                                end_at: item.end_at,
                                status: item.status,
                            });
                        });

                        workbook.xlsx.writeBuffer().then((buffer) => {
                            const blob = new Blob([buffer], {
                                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            let year = this.focus.split('-')[0]
                            let month = this.focus.split('-')[1]

                            a.href = url;
                            a.download = '品保行程' + year + '年' + month + '月.xlsx';
                            a.click();
                            window.URL.revokeObjectURL(url);
                        });
                    }
                },

                watch: {
                    'editedItem.restaurant': function() {
                        if (this.editedItem.restaurant && this.editedItem.date) {
                            this.getMeals()
                        }
                    },

                    focus() {
                        this.updateRange()
                    }
                },

                mounted() {
                    this.updateRange()
                },


            });
        </script>
    </x-slot:footerFiles>
</x-base-layout>
