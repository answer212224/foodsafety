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
    </x-slot:headerFiles>

    <div id="app">
        <v-app v-cloak>
            <v-main class="grey lighten-4">
                <v-container>

                    <v-toolbar color="primary darken-2" dark dense>
                        @can('create-project')
                            <v-btn icon @click="editItem(-1)">
                                <v-icon>mdi-plus</v-icon>
                            </v-btn>
                        @endcan
                        <v-toolbar-title>{{ $title }}</v-toolbar-title>

                        <v-divider class="mx-4" inset vertical></v-divider>
                        <v-spacer></v-spacer>
                    </v-toolbar>

                    <v-data-table :items="projects" :loading="loading" class="elevation-1 my-table"
                        item-key="id" :search="search" :headers="headers" sort-by="id" sort-desc fixed-header
                        height="calc(100vh - 300px)">
                        <template v-slot:top>
                            <v-row align="center" class="pa-2">
                                <v-col cols="12" sm="6">
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="search" append-icon="mdi-magnify" label="專案名稱、專案描述"
                                        single-line></v-text-field>
                                </v-col>
                            </v-row>
                        </template>

                        <template v-slot:item.status="{ item }">
                            <v-chip color="success" small dark v-if="item.status">啟用</v-chip>
                            <v-chip color="error" small dark v-else>停用</v-chip>
                        </template>
                        <template v-slot:item.actions="{ item }">
                            @can('update-project')
                                <v-icon small class="mr-2" @click="editItem(item)">mdi-pencil</v-icon>
                            @endcan

                        </template>
                    </v-data-table>

                </v-container>

                <v-dialog v-model="dialog" max-width="800px" @click:outside="close">
                    <v-card>
                        <v-card-title>
                            <span class="headline">@{{ formTitle }}</span>
                        </v-card-title>

                        <v-card-text>
                            <v-container>
                                <v-form v-model="valid" ref="form">
                                    <v-row>
                                        {{-- 專案名稱 --}}
                                        <v-col cols="12" sm="6">
                                            <v-text-field v-model="editedItem.name" :rules="[v => !!v || '請輸入專案名稱']"
                                                prepend-icon="mdi-format-list-bulleted-type" label="專案名稱"
                                                required></v-text-field>
                                        </v-col>
                                        {{-- 專案狀態 --}}
                                        <v-col cols="12" sm="6">
                                            <v-select v-model="editedItem.status" :items="statusItems" label="專案狀態"
                                                prepend-icon="mdi-format-list-bulleted-type" required></v-select>
                                        </v-col>

                                        {{-- 專案月份 --}}
                                        <v-col cols="12" sm="6">
                                            <v-menu ref="menu" v-model="menu" :close-on-content-click="false"
                                                :return-value.sync="date" transition="scale-transition" offset-y
                                                max-width="290px" min-width="auto">
                                                <template v-slot:activator="{ on, attrs }">
                                                    <v-text-field v-model="date" label="專案月份"
                                                        prepend-icon="mdi-calendar" readonly v-bind="attrs"
                                                        v-on="on"></v-text-field>
                                                </template>
                                                <v-date-picker v-model="date" type="month" no-title scrollable
                                                    locale="zh-tw" @input="$refs.menu.save(date)">
                                                </v-date-picker>
                                            </v-menu>
                                        </v-col>

                                        {{-- (內外場)食安缺失子項目 --}}
                                        <v-col cols="12" sm="6">
                                            <v-combobox v-model="editedItem.description" :items="projectDefects"
                                                prepend-icon="mdi-format-list-bulleted-type" label="食安缺失子項目" required
                                                :rules="[v => !!v || '請選擇食安缺失子項目']">
                                            </v-combobox>
                                        </v-col>
                                    </v-row>
                                </v-form>
                            </v-container>
                        </v-card-text>

                        <v-card-actions>
                            <v-spacer></v-spacer>
                            <v-btn color="blue darken-1" text @click="close">取消</v-btn>
                            <v-btn color="blue darken-1" text @click="save" :disabled="!valid">儲存</v-btn>
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
                    search: '',
                    projects: [],
                    formTitle: '',
                    headers: [{
                            text: 'ID',
                            align: 'start',
                            value: 'id',
                        },
                        {
                            text: '專案名稱',
                            value: 'name',
                        },
                        {
                            text: '專案描述',
                            value: 'description',
                        },
                        {
                            text: '狀態',
                            value: 'status',
                        },
                        {
                            text: '動作',
                            value: 'actions',
                            sortable: false,
                        },
                    ],
                    dialog: false,
                    editedIndex: -1,
                    editedItem: {
                        id: 0,
                        name: '',
                        description: '',
                        status: 1,
                    },
                    defaultItem: {
                        id: 0,
                        name: '',
                        description: '',
                        status: 1,
                    },
                    statusItems: [{
                            text: '啟用',
                            value: 1,
                        },
                        {
                            text: '停用',
                            value: 0,
                        },
                    ],
                    valid: false,
                    date: '2024-03',
                    menu: false,
                    projectDefects: [],
                },
                methods: {
                    getProjects() {
                        this.loading = true;
                        axios.get('/api/projects')
                            .then((response) => {
                                this.projects = response.data.data;
                            })
                            .catch((error) => {
                                alert(error.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },
                    getProjectDefects() {
                        this.loading = true;
                        axios.get('/api/project-defects', {
                                params: {
                                    month: this.date,
                                }
                            })
                            .then((response) => {
                                this.projectDefects = response.data.data;
                                if (this.projectDefects.length > 0) {
                                    this.projectDefects = this.projectDefects.map((item) => {
                                        return [
                                            '(內場)' + item.description,
                                            '(外場)' + item.description,
                                        ]
                                    });
                                    this.projectDefects = this.projectDefects.flat();
                                }

                            })
                            .catch((error) => {
                                alert(error.response.data.message);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    },

                    editItem(item) {
                        this.editedIndex = this.projects.indexOf(item);


                        if (this.editedIndex == -1) {
                            this.formTitle = '新增專案';
                        } else {
                            this.formTitle = '編輯專案';
                            this.editedItem = Object.assign({}, item);
                        }

                        this.dialog = true;
                    },



                    close() {
                        this.dialog = false;
                        setTimeout(() => {
                            this.editedItem = Object.assign({}, this.defaultItem);
                            this.editedIndex = -1;
                        }, 300);
                    },

                    save() {
                        if (this.editedIndex > -1) {
                            this.loading = true;
                            axios.put('/api/projects/update/' + this.editedItem.id, this.editedItem)
                                .then((response) => {
                                    alert('更新成功')
                                    this.getProjects();
                                })
                                .catch((error) => {
                                    alert(error.response.data.message);
                                })
                                .finally(() => {
                                    this.loading = false;

                                });
                        } else {
                            this.loading = true;
                            axios.post('/api/projects/store', this.editedItem)
                                .then((response) => {
                                    alert('新增成功')
                                    this.getProjects();
                                })
                                .catch((error) => {
                                    alert(error.response.data.message);
                                })
                                .finally(() => {
                                    this.loading = false;
                                });
                        }
                        this.close();
                    },
                },

                watch: {
                    date() {
                        this.getProjectDefects();
                    },
                },

                mounted() {
                    this.getProjects();
                    this.getProjectDefects();
                }
            });
        </script>
    </x-slot:footerFiles>


</x-base-layout>
