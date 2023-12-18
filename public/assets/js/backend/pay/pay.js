define(["jquery", "bootstrap", "backend", "table", "form"], function (
    $,
    undefined,
    Backend,
    Table,
    Form
) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: "pay/pay/index" + location.search,
                    del_url: "pay/pay/del",
                    supplementary_url: "pay/pay/supplementary",
                    table: "pay",
                },
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: "id",
                sortName: "id",
                fixedColumns: true,
                fixedRightNumber: 1,
                columns: [
                    [
                        { checkbox: true },
                        { field: "id", title: __("Id") },
                        {
                            field: "code",
                            title: __("Code"),
                            operate: "LIKE",
                            table: table,
                            class: "autocontent",
                            formatter: Table.api.formatter.content,
                        },
                        {
                            field: "name",
                            title: __("Name"),
                            operate: "LIKE",
                            table: table,
                            class: "autocontent",
                            formatter: Table.api.formatter.content,
                        },
                        {
                            field: "third",
                            title: __("Third"),
                            operate: "LIKE",
                            table: table,
                            class: "autocontent",
                            formatter: Table.api.formatter.content,
                        },
                        {
                            field: "paytype",
                            title: __("Paytype"),
                            searchList: {
                                0: __("微信支付"),
                                1: __("支付宝支付"),
                            },
                            formatter: Table.api.formatter.normal,
                        },
                        {
                            field: "originalprice",
                            title: __("Originalprice"),
                            operate: "BETWEEN",
                        },
                        {
                            field: "price",
                            title: __("Price"),
                            operate: "BETWEEN",
                        },
                        {
                            field: "remarks",
                            title: __("Remarks"),
                            operate: "LIKE",
                            table: table,
                            class: "autocontent",
                            formatter: function (value) {
                                if (!value) {
                                    return "-";
                                }

                                return `<div class="autocontent-item " style="white-space: nowrap; text-overflow:ellipsis; overflow: hidden; max-width:250px;">${value}</div>`;
                            },
                        },
                        // {field: 'paypage', title: __('Paypage'), searchList: {"0":__('Paypage 0'),"1":__('Paypage 1'),"2":__('Paypage 2')}, formatter: Table.api.formatter.normal},
                        // {field: 'reurl', title: __('Reurl'), operate: 'LIKE', formatter: Table.api.formatter.url},
                        // {field: 'callbackurl', title: __('Callbackurl'), operate: 'LIKE', formatter: Table.api.formatter.url},
                        {
                            field: "status",
                            title: __("Status"),
                            searchList: {
                                0: __("待支付"),
                                1: __("已支付"),
                                2: __("已关闭"),
                            },
                            formatter: Table.api.formatter.status,
                        },
                        // {field: 'wxcode', title: __('Wxcode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'zfbcode', title: __('Zfbcode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        // {field: 'qrcode', title: __('Qrcode'), operate: 'LIKE', table: table, class: 'autocontent', formatter: Table.api.formatter.content},
                        {
                            field: "createtime",
                            title: __("Createtime"),
                            operate: "RANGE",
                            addclass: "datetimerange",
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime,
                        },
                        {
                            field: "paytime",
                            title: __("Paytime"),
                            operate: "RANGE",
                            addclass: "datetimerange",
                            autocomplete: false,
                            formatter: Table.api.formatter.datetime,
                        },
                        {
                            field: "operate",
                            title: __("Operate"),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: "storage",
                                    title: "补单",
                                    classname:
                                        "btn btn-xs btn-success btn-ajax",
                                    extend: 'data-toggle="tooltip" data-container="body"',
                                    icon: "fa fa-leaf",
                                    confirm: "确认补单吗？",
                                    url: $.fn.bootstrapTable.defaults.extend
                                        .supplementary_url,
                                    success: function (data, ret) {
                                        $(".btn-refresh").trigger("click");
                                    },
                                    visible: function (row) {
                                        return row.status == 0 ? true : false;
                                    },
                                },
                            ],
                        },
                    ],
                ],
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
        },
    };
    return Controller;
});
