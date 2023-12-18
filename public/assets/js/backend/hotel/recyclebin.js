define(["jquery", "bootstrap", "backend", "table", "form"], function (
    $,
    undefined,
    Backend,
    Table,
    Form
) {
    // 定义一个控制器
    var Controller = {
        index: function () {
            // 给选项卡绑定事件 切换选项卡的时候触发
            $(`a[data-toggle="tab"]`).on("shown.bs.tab", function () {
                var tab = $($(this).attr("href"));
                if (tab.length <= 0) return; // 为空就停下

                // 两个选项卡，分别给两个不同的请求方法
                // 根据锚点切换，切换的时候触发选项卡的事件
                Controller.table[tab.attr("id")].call(this);
            });

            // 触发第一个选项卡的事件
            Controller.table["room"]();
        },

        table: {
            // 商品回收站
            room: function () {
                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        room_url: `hotel/recyclebin/index?action=room`, // 酒店回收的地址
                        restore_url: `hotel/recyclebin/restore`, // 恢复的地址
                        destroy_url: `hotel/recyclebin/destroy`, // 销毁的地址
                        table: "hotel_room", // 表名
                    },
                });

                // 获取view视图里面的表格
                var RoomTable = $("#RoomTable");

                // 渲染列表数据
                // $.ajax({
                RoomTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.room_url, // 请求地址
                    toolbar: "#RoomToolbar", // 工具栏
                    pk: "id", // 主键
                    sortName: "id", // 排序字段
                    sortOrder: "desc", // 排序方式

                    // 渲染的数据
                    columns: [
                        // 渲染的字段部分
                        { checkbox: true },
                        { field: "id", title: "ID" },
                        { field: "name", title: __("Name") },
                        {
                            field: "thumb_text",
                            title: __("Thumb"),
                            formatter: Table.api.formatter.image,
                        },
                        {
                            field: "flag",
                            title: __("Flag"),
                            formatter: Table.api.formatter.flag,
                        },
                        { field: "total", title: __("Total") },
                        { field: "price", title: __("Price") },
                        {
                            field: "deletetime",
                            title: __("DeleteTime"),
                            operate: "RANGE",
                            addclass: "datetimerange",
                            formatter: Table.api.formatter.datetime,
                        },

                        // 最后一排的操作按钮组
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: RoomTable,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                // 定义自定义按钮
                                {
                                    name: "restore", // 跟table页面中绑定一样
                                    title: "还原",
                                    icon: "fa fa-reply", // 图标
                                    classname:
                                        "btn btn-xs btn-success btn-magic btn-ajax",
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .restore_url + `?action=room`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认恢复数据",
                                    success: function () {
                                        // 刷新表格
                                        RoomTable.bootstrapTable("refresh");
                                    },
                                },
                                {
                                    name: "destroy", // 跟table页面中绑定一样
                                    title: "销毁",
                                    icon: "fa fa-trash", // 图标
                                    classname:
                                        "btn btn-xs btn-danger btn-magic btn-ajax",
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .destroy_url + `?action=room`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认真实销毁数据",
                                    success: function () {
                                        // 刷新表格
                                        RoomTable.bootstrapTable("refresh");
                                    },
                                },
                            ],
                        },
                    ],
                });

                // 为表格绑定事件
                Table.api.bindevent(RoomTable);

                // 绑定按钮事件
                $(".btn-restore").on("click", function () {
                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(RoomTable);

                    // 弹框
                    layer.confirm(
                        "是否确认恢复数据",
                        { title: "恢复标题", btn: ["是", "否"] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                {
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .restore_url +
                                        `?ids=${ids}&action=room`,
                                },
                                () => {
                                    // 关闭弹框
                                    layer.close(index);

                                    // 刷新表格
                                    RoomTable.bootstrapTable("refresh");
                                }
                            );
                        }
                    );
                });

                $(".btn-destroy").on("click", function () {
                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(RoomTable);

                    // 弹框
                    layer.confirm(
                        "是否确认真实删除数据",
                        { title: "真实删除标题", btn: ["是", "否"] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                {
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .destroy_url +
                                        `?ids=${ids}&action=room`,
                                },
                                () => {
                                    // 关闭弹框
                                    layer.close(index);

                                    // 刷新表格
                                    RoomTable.bootstrapTable("refresh");
                                }
                            );
                        }
                    );
                });
            },

            // 商品订单回收站
            order: function () {
                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        order_url: `hotel/recyclebin/index?action=order`, // 课程回收的地址
                        restore_url: `hotel/recyclebin/restore`, // 恢复的地址
                        destroy_url: `hotel/recyclebin/destroy`, // 销毁的地址
                        table: "hotel_order", // 表名
                    },
                });

                // 获取view视图里面的表格
                var OrderTable = $("#OrderTable");

                // 渲染列表数据
                // $.ajax({
                OrderTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.order_url, // 请求地址
                    toolbar: "#OrderToolbar", // 工具栏
                    pk: "id", // 主键
                    sortName: "id", // 排序字段
                    sortOrder: "desc", // 排序方式

                    // 渲染的数据
                    columns: [
                        // 渲染的字段部分
                        { checkbox: true },
                        { field: "id", title: "ID" },
                        {
                            field: "business.nickname",
                            title: __("BusNickname"),
                        },
                        { field: "room.name", title: __("RoomName") },
                        {
                            field: "starttime",
                            title: __("StartTime"),
                            operate: "RANGE",
                            addclass: "datetimerange",
                            formatter: Table.api.formatter.datetime,
                        },
                        {
                            field: "endtime",
                            title: __("EndTime"),
                            operate: "RANGE",
                            addclass: "datetimerange",
                            formatter: Table.api.formatter.datetime,
                        },
                        {
                            field: "origin_price",
                            title: __("OriginPrice"),
                            formatter: Table.api.formatter.price,
                        },
                        {
                            field: "price",
                            title: __("RPrice"),
                            formatter: Table.api.formatter.price,
                        },
                        {
                            field: "status",
                            title: __("Status"),
                            searchList: {
                                1: __("已支付"),
                                2: __("已入住"),
                                3: __("已退房"),
                                4: __("已评价"),
                                "-1": __("申请退款"),
                                "-2": __("审核成功"),
                                "-3": __("审核失败"),
                            },
                            formatter: Table.api.formatter.status,
                        },
                        {
                            field: "deletetime",
                            title: __("DeleteTime"),
                            operate: "RANGE",
                            addclass: "datetimerange",
                            formatter: Table.api.formatter.datetime,
                        },

                        // 最后一排的操作按钮组
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: OrderTable,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                // 定义自定义按钮
                                {
                                    name: "restore", // 跟table页面中绑定一样
                                    title: "还原",
                                    icon: "fa fa-reply", //图标
                                    classname:
                                        "btn btn-xs btn-success btn-magic btn-ajax",
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .restore_url + `?action=order`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认恢复数据",
                                    success: function () {
                                        // 刷新表格
                                        OrderTable.bootstrapTable("refresh");
                                    },
                                },
                                {
                                    name: "destroy", // 跟table页面中绑定一样
                                    title: "销毁",
                                    icon: "fa fa-trash", // 图标
                                    classname:
                                        "btn btn-xs btn-danger btn-magic btn-ajax",
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .destroy_url + `?action=order`,
                                    extend: "data-toggle='tooltip'",
                                    confirm: "是否确认真实销毁数据",
                                    success: function () {
                                        //刷新表格
                                        OrderTable.bootstrapTable("refresh");
                                    },
                                },
                            ],
                        },
                    ],
                });

                // 为表格绑定事件
                Table.api.bindevent(OrderTable);

                // 绑定按钮事件
                $(".btn-restore").on("click", function () {
                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(OrderTable);

                    // 弹框
                    layer.confirm(
                        "是否确认恢复数据",
                        { title: "恢复标题", btn: ["是", "否"] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                {
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .restore_url +
                                        `?ids=${ids}&action=order`,
                                },
                                () => {
                                    // 关闭弹框
                                    layer.close(index);

                                    // 刷新表格
                                    OrderTable.bootstrapTable("refresh");
                                }
                            );
                        }
                    );
                });

                $(".btn-destroy").on("click", function () {
                    // 获取table勾选的id值
                    var ids = Table.api.selectedids(OrderTable);

                    // 弹框
                    layer.confirm(
                        "是否确认真实删除数据",
                        { title: "真实删除标题", btn: ["是", "否"] },
                        function (index) {
                            // 发送ajax请求
                            Backend.api.ajax(
                                {
                                    url:
                                        $.fn.bootstrapTable.defaults.extend
                                            .destroy_url +
                                        `?ids=${ids}&action=order`,
                                },
                                () => {
                                    // 关闭弹框
                                    layer.close(index);

                                    // 刷新表格
                                    OrderTable.bootstrapTable("refresh");
                                }
                            );
                        }
                    );
                });
            },
        },

        // 回收
        restore: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 销毁
        destroy: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        api: {
            // JS模块化的全局方法
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        },
    };

    // 模块返回值
    return Controller;
});
