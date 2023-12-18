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
                    index_url: "hotel/coupon/index", // 列表查询的请求控制器方法
                    add_url: "hotel/coupon/add", // 添加的控制器地址
                    edit_url: "hotel/coupon/edit", // 编辑的控制器地址
                    del_url: "hotel/coupon/del", // 删除的控制器地址
                    table: "hotel_coupon", // 当前表格的名称
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

                // 渲染的数据部分
                columns: [
                    // 渲染的字段部分
                    { checkbox: true },
                    { field: "id", title: "ID" },
                    { field: "title", title: __("Title") },
                    {
                        field: "thumb_text",
                        title: __("Thumb"),
                        formatter: Table.api.formatter.image,
                    },
                    { field: "rate", title: __("Rate") },
                    { field: "total", title: __("Total") },
                    {
                        field: "createtime",
                        title: __("CreateTime"),
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
                        field: "status_text",
                        title: __("Status"),
                        searchList: { 0: __("活动结束"), 1: __("正在活动中") },
                        formatter: Table.api.formatter.status,
                    },

                    // 最后一排的操作按钮组
                    {
                        field: "operate",
                        title: __("Operate"),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                    },
                ],
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        edit: function () {
            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        del: function () {
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
