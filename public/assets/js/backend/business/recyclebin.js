define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "business/recyclebin/index",
                    del_url: "business/recyclebin/del",
                    red_url: "business/recyclebin/reduction",
                    table: "business",
                },
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "id",
                sortName: "business.createtime",
                columns: [
                    [
                        { checkbox: true },
                        { field: "id", title: __("Id"), sortable: true },
                        {
                            field: "nickname",
                            title: __("Nickname"),
                            operate: "LIKE",
                        },
                        {
                            field: "mobile",
                            title: __("Mobile"),
                            operate: "LIKE",
                        },
                        {
                            field: "source.name",
                            title: __("Source"),
                            operate: "LIKE",
                        },
                        {
                            field: "gender",
                            title: __("Gender"),
                            searchList: { 0: __("保密"), 1: __("男"), 2: __("女") },
                            sortable: false,
                            searchable: false,
                            formatter: Table.api.formatter.normal,
                        },
                        {
                            field: "deal",
                            title: __("Deal"),
                            searchList: { 0: __("未成交"), 1: __("已成交") },
                            formatter: Table.api.formatter.normal,
                        },
                        {
                            field: "admin.username",
                            title: __("Admin"),
                            operate: "LIKE",
                        },
                        {
                            field: "visit.createtime",
                            title: __("Screatetime"),
                            sortable: false,
                            searchable: false,
                            formatter: Table.api.formatter.datetime,
                            operate: "RANGE",
                            addclass: "datetimerange",
                        },
                        {
                            field: "visit.content",
                            title: __("Vcontent"),
                            sortable: false,
                            searchable: false,
                        },
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: "budrecycle",
                                    confirm: "确定要还原吗",
                                    title: "还原数据",
                                    extend: 'data-toggle="tooltip"',
                                    icon: "fa fa-reply",
                                    classname:
                                        "btn btn-xs btn-success btn-ajax",
                                    url: "business/recyclebin/reduction?ids={id}",
                                    success: function () {
                                        $(".btn-refresh").trigger("click");
                                    },
                                },
                            ],
                        },
                    ],
                ],
            });

            // 还原，确认框的方法
            $(".btn-reduction").on("click", function () {
                let ids = Table.api.selectedids(table);
                ids = ids.toString();

                layer.confirm(
                    "确定要还原吗?",
                    { title: "还原", btn: ["是", "否"] },
                    function (index) {

                        $.post(
                            "business/recyclebin/reduction",
                            { ids: ids },
                            function (response) {
                                if (response.code == 1) {
                                    Toastr.success(response.msg);
                                    $(".btn-refresh").trigger("click");
                                } else {
                                    Toastr.error(response.msg);
                                }
                            },
                            "json"
                        );

                        layer.close(index);
                    }
                );
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        del: function () {
            Controller.api.bindevent();
        },

        reduction: function () {
            Controller.api.bindevent();
        },

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    
    return Controller;
});