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
            // 初始化表格参数配置
            // 配置整个表格中增删查改请求控制器地址，用的ajax的方式请求
            Table.api.init({
                extend: {
                    index_url: "hotel/order/index", // 列表查询的请求控制器方法
                    info_url: "hotel/order/info", // 查询详情的请求控制器方法
                    del_url: "hotel/order/del", // 删除的控制器地址
                    checkin_url: "hotel/order/checkin", // 入住的控制器地址
                    checkout_url: "hotel/order/checkout", // 退房的控制器地址
                    allow_url: "hotel/order/allow", // 允许退款的控制器地址
                    refuse_url: "hotel/order/refuse", // 拒绝退款的控制器地址
                    table: "hotel_order", // 当前表格的名称
                },
            });

            // 获取view视图里面的dom元素table元素
            var table = $("#table");

            // 渲染列表数据
            // $.ajax({
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url, // 请求地址
                toolbar: ".toolbar", // 工具栏
                pk: "id", // 默认主键字段名
                sortName: "id", // 排序的字段名
                sortOrder: "desc", // 排序方式
                fixedColumns: true, // 固定列
                fixedRightNumber: 1, // 固定右侧第几列

                // 渲染的数据部分
                columns: [
                    // 渲染的字段部分
                    { checkbox: true },
                    { field: "id", title: "ID" },
                    { field: "business.nickname", title: __("BusNickname") },
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
                        title: __("Price"),
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

                    // 最后一排的操作按钮组
                    {
                        field: "operate",
                        title: __("Operate"),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [
                            {
                                name: "info",
                                title: "订单详情",
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-primary btn-dialog",
                                icon: "fa fa-eye",
                                url: $.fn.bootstrapTable.defaults.extend
                                    .info_url,
                            },

                            // 入住
                            {
                                visible: function (row) {
                                    if (row.status == "1") {
                                        return true;
                                    }
                                    return false;
                                },
                                name: "checkin",
                                title: "已入住",
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-success btn-ajax",
                                icon: "fa fa-check",
                                url: "hotel/order/checkin",
                                refresh: true,
                            },

                            // 退房
                            {
                                visible: function (row) {
                                    if (row.status == "2") {
                                        return true;
                                    }
                                    return false;
                                },
                                name: "checkout",
                                title: "已退房",
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-danger btn-ajax",
                                icon: "fa fa-close",
                                url: "hotel/order/checkout",
                                refresh: true,
                            },

                            // 允许退款
                            {
                                visible: function (row) {
                                    if (row.status == "-1") {
                                        return true;
                                    }
                                    return false;
                                },
                                name: "allow",
                                title: "允许退款",
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-success btn-ajax",
                                icon: "fa fa-check",
                                url: "hotel/order/allow",
                                refresh: true,
                            },

                            // 拒绝退款
                            {
                                visible: function (row) {
                                    if (row.status == "-1") {
                                        return true;
                                    }
                                    return false;
                                },
                                name: "refuse",
                                title: "拒绝退款",
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-danger btn-ajax",
                                icon: "fa fa-close",
                                url: "hotel/order/refuse",
                                refresh: true,
                            },
                        ],
                    },
                ],
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        // 详情页
        info: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 删除
        del: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 允许退款
        allow: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 拒绝退款
        refuse: function () {
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

    return Controller;
});
