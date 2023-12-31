define(['jquery', 'bootstrap', 'backend', 'table', 'form',], function ($, undefined, Backend, Table, Form) {

    // 定义一个控制器
    var Controller = {
        index: function () {

            // 给选项卡绑定事件 切换选项卡的时候触发
            $(`a[data-toggle="tab"]`).on('shown.bs.tab', function () {

                // 获取当前选中的选项卡的id
                // var tab = $(e.target).attr('href');
                var tab = $($(this).attr('href'))
                if (tab.length <= 0) return; // 为空就停下

                // 两个选项卡，分别给两个不同的请求方法
                // 根据锚点切换，切换的时候触发选项卡的事件
                Controller.table[tab.attr('id')].call(this);
            });

            // 触发第一个选项卡的事件
            Controller.table['storage']();
        },

        table: {

            // 课程回收站
            storage: function () {

                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        storage_url: `depot/recyclebin/index?action=storage`, // 入库回收的地址
                        restore_url: `depot/recyclebin/restore`, // 恢复的地址
                        destroy_url: `depot/recyclebin/destroy`, // 销毁的地址
                        table: 'depot_storage', // 表名
                    }
                });

                // 获取view视图里面的表格
                var StorageTable = $("#StorageTable");

                // 渲染列表数据
                StorageTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.storage_url, // 请求地址

                    toolbar: '#StorageToolbar', // 工具栏
                    pk: 'id', // 主键
                    sortName: 'createtime', // 排序字段
                    sortOrder: 'desc', // 排序方式

                    // 渲染的数据
                    columns: [
                        // 渲染的字段部分
                        {
                            checkbox: true,
                            formatter: function (value, row, index) {
                                if (row.status == 1 || row.status == 2 || row.status == 3) {
                                    return { disabled: true };
                                }
                            }
                        },
                        {
                            field: 'id',
                            title: 'ID'
                        },
                        {
                            field: 'code',
                            title: __('Code'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'supplier.name',
                            title: __('Supplierid'),
                        },
                        {
                            field: 'type',
                            title: __('Type'),
                            searchList: { "1": __('直销入库'), "2": __('退货入库') },
                            formatter: Table.api.formatter.status

                        },
                        {
                            field: 'amount',
                            title: __('Amount'),
                            operate: 'BETWEEN'
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: { "0": __('待审批'), "1": __('审批失败'), "2": __('待入库'), "3": __('入库完成') },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'createtime',
                            title: __('Screatetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },

                        // 最后一排的操作按钮组
                        {
                            field: "operate",
                            title: __('Operate'),
                            table: StorageTable,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [

                                // 定义自定义按钮
                                {
                                    name: 'restore', // 跟table页面中绑定一样
                                    title: '还原',
                                    icon: 'fa fa-reply', // 图标
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: $.fn.bootstrapTable.defaults.extend.restore_url + `?action=storage`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认恢复数据",
                                    success: function () {

                                        // 刷新表格
                                        StorageTable.bootstrapTable('refresh')
                                    }
                                },
                                {
                                    name: 'destroy', // 跟table页面中绑定一样
                                    title: '销毁',
                                    icon: 'fa fa-trash', // 图标
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: $.fn.bootstrapTable.defaults.extend.destroy_url + `?action=storage`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认真实销毁数据",
                                    success: function () {

                                        // 刷新表格
                                        StorageTable.bootstrapTable('refresh')
                                    }
                                },
                            ]
                        }
                    ],
                })

                // 为表格绑定事件
                Table.api.bindevent(StorageTable);

                // 绑定按钮事件
                $(".btn-restore").on('click', function () {

                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(StorageTable)

                    // 弹框
                    layer.confirm(
                        '是否确认恢复数据',
                        { title: "恢复标题", btn: ['是', '否'] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                { url: $.fn.bootstrapTable.defaults.extend.restore_url + `?ids=${ids}&action=storage` },
                                () => {
                                    // 关闭弹框
                                    layer.close(index)

                                    // 刷新表格
                                    StorageTable.bootstrapTable('refresh')
                                }
                            )
                        }
                    )
                })

                $(".btn-destroy").on('click', function () {

                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(StorageTable)

                    // 弹框
                    layer.confirm(
                        '是否确认真实删除数据',
                        { title: "真实删除标题", btn: ['是', '否'] },
                        function (index) {

                            // 发送ajax请求
                            Backend.api.ajax(
                                { url: $.fn.bootstrapTable.defaults.extend.destroy_url + `?ids=${ids}&action=storage` },
                                () => {
                                    // 关闭弹框
                                    layer.close(index)

                                    // 刷新表格
                                    StorageTable.bootstrapTable('refresh')
                                }
                            )
                        }
                    )
                })
            },

            // 课程订单回收站
            back: function () {

                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        back_url: `depot/recyclebin/index?action=back`, // 退货回收的地址
                        restore_url: `depot/recyclebin/restore`, // 恢复的地址
                        destroy_url: `depot/recyclebin/destroy`, // 销毁的地址
                        table: 'depot_back', // 表名
                    }
                });

                // 获取view视图里面的表格
                var BackTable = $("#BackTable");

                // 渲染列表数据
                // $.ajax({
                BackTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.back_url, // 请求地址
                    toolbar: '#BackToolbar', // 工具栏
                    pk: 'id', // 主键
                    sortName: 'createtime', // 排序字段
                    sortOrder: 'desc', // 排序方式

                    // 渲染的数据
                    columns: [ // 渲染的字段部分
                        {
                            checkbox: true,
                            formatter: function (value, row, index) {
                                if (row.status == 2 || row.status == 3) {
                                    return { disabled: true };
                                }
                            }
                        },
                        {
                            field: 'id',
                            title: __('ID')
                        },
                        {
                            field: 'code',
                            title: __('BackCode'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'ordercode',
                            title: __('Ordercode'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'business.nickname',
                            title: __('Busid'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'contact',
                            title: __('Contact'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'mobile',
                            title: __('Phone'),
                            operate: 'LIKE'
                        },
                        {
                            field: 'amount',
                            title: __('Amount'),
                            operate: 'BETWEEN'
                        },
                        {
                            field: 'status',
                            title: __('Status'),
                            searchList: { "0": __('未审核'), "1": __('已审核，未收货'), "2": __('已收货，未入库'), "3": __('已入库'), "-1": __('审核不通过') },
                            formatter: Table.api.formatter.status
                        },
                        {
                            field: 'createtime',
                            title: __('Createtime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime

                        },

                        // 最后一排的操作按钮组
                        {
                            field: "operate",
                            title: __('Operate'),
                            table: BackTable,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [

                                // 定义自定义按钮
                                {
                                    name: 'restore', // 跟table页面中绑定一样
                                    title: '还原',
                                    icon: 'fa fa-reply', //图标
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    url: $.fn.bootstrapTable.defaults.extend.restore_url + `?action=back`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认恢复数据",
                                    success: function () {

                                        // 刷新表格
                                        BackTable.bootstrapTable('refresh')
                                    }
                                },
                                {
                                    name: 'destroy', // 跟table页面中绑定一样
                                    title: '销毁',
                                    icon: 'fa fa-trash', // 图标
                                    classname: 'btn btn-xs btn-danger btn-magic btn-ajax',
                                    url: $.fn.bootstrapTable.defaults.extend.destroy_url + `?action=back`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认真实销毁数据",
                                    success: function () {

                                        //刷新表格
                                        BackTable.bootstrapTable('refresh')
                                    }
                                },
                            ]
                        }
                    ],
                })

                // 为表格绑定事件
                Table.api.bindevent(BackTable);

                // 绑定按钮事件
                $(".btn-restore").on('click', function () {

                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(BackTable)

                    // 弹框
                    layer.confirm(
                        '是否确认恢复数据',
                        { title: "恢复标题", btn: ['是', '否'] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                { url: $.fn.bootstrapTable.defaults.extend.restore_url + `?ids=${ids}&action=back` },
                                () => {
                                    // 关闭弹框
                                    layer.close(index)

                                    // 刷新表格
                                    BackTable.bootstrapTable('refresh')
                                }
                            )
                        }
                    )
                })

                $(".btn-destroy").on('click', function () {

                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(BackTable)

                    // 弹框
                    layer.confirm(
                        '是否确认真实删除数据',
                        { title: "真实删除标题", btn: ['是', '否'] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                { url: $.fn.bootstrapTable.defaults.extend.destroy_url + `?ids=${ids}&action=back` },
                                () => {
                                    // 关闭弹框
                                    layer.close(index)

                                    // 刷新表格
                                    BackTable.bootstrapTable('refresh')
                                }
                            )
                        }
                    )
                })
            },
        },

        // 课程回收
        restore: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent()
        },

        // 课程销毁
        destroy: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent()
        },

        api: {

            // JS模块化的全局方法
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    }

    // 模块返回值
    return Controller;

});