define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        //详细
        index: function () {
            // 初始化
            Table.api.init()

            // 绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.length > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }

                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });

            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
        },
        table: {
            info: function () {

            },
            visit: function () {
                // 回访列表
                var table2 = $("#table2");
                table2.bootstrapTable({
                    url: 'business/privateseainfo/visit?ids=' + Fast.api.query('ids'),
                    extend: {
                        add_url: 'business/privateseainfo/add?ids=' + Fast.api.query('ids'),
                        edit_url: 'business/privateseainfo/edit',
                        del_url: 'business/privateseainfo/del',
                        table: 'business_visit',
                    },
                    toolbar: '#toolbar2',
                    sortName: 'visit.createtime',
                    search: false,
                    columns: [
                        [
                            { checkbox: true },
                            { field: 'id', title: __('ID'), sortable: true },
                            { field: 'business.nickname', title: __('Nickname'), operate: 'LIKE' },
                            { field: 'content', title: __('Content'), operate: 'LIKE' },
                            { field: 'admin.nickname', title: __('Admin'), sortable: false, searchable: false },
                            { field: 'createtime', title: __('Time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', },
                            {
                                field: 'operate',
                                title: __('Operate'),
                                table: table2,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                            }
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table2);
            },
            // 申请记录
            receive: function () {
                // 表格1
                var table3 = $("#table3");

                table3.bootstrapTable({
                    url: 'business/privateseainfo/receive?ids=' + Fast.api.query('ids'),
                    extends: {
                        table: 'business_receive',
                    },
                    toolbar: '#toolbar3',
                    sortName: 'receive.applytime',
                    search: false,
                    columns: [
                        [
                            { checkbox: true },
                            { field: 'id', title: __('ID'), sortable: true },
                            { field: 'business.nickname', title: __('Nickname'), operate: 'LIKE' },
                            { field: 'admin.nickname', title: __('Admin'), sortable: false, searchable: false },
                            { field: 'status_text', title: __('Status'), sortable: false, searchable: false },
                            { field: 'applytime', title: __('Apply'), sortable: true, searchable: true, formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', }
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table3);
            }
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        del: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"))
            },
        },
    };
    return Controller;
});