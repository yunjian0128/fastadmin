define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'product/order/index' + location.search,
                    info_url: 'product/order/info',
                    deliver_url: 'product/order/deliver',
                    refund_url: 'product/order/refund',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                fixedColumns: true, // 固定列
                fixedRightNumber: 1, // 固定右侧第几列
                columns: [
                    [
                        {
                            checkbox: true
                        },
                        { 
                            field: 'id', 
                            title: 'ID' 
                        },
                        { 
                            field: 'code', 
                            title: __('Code'), 
                            operate: 'LIKE', 
                            table: table, 
                            class: 'autocontent', 
                            formatter: Table.api.formatter.content 
                        },
                        { 
                            field: 'business.nickname', 
                            title: __('Busid'), 
                            operate: 'LIKE' 
                        },
                        { 
                            field: 'amount', 
                            title: __('Amount'), 
                            operate: 'BETWEEN' 
                        },
                        { 
                            field: 'express.name', 
                            title: __('Expressid') 
                        },
                        {
                            field: 'expresscode', 
                            title: __('Expresscode'), 
                            operate: 'LIKE', 
                            table: table, 
                            class: 'autocontent', 
                            formatter: function (value) {
                                if (!value) {
                                    return '-';
                                }

                                return `<div class="autocontent-item " style="white-space: nowrap; text-overflow:ellipsis; overflow: hidden; max-width:250px;">${value}</div>`
                            }
                        },

                        { 
                            field: 'status', 
                            title: __('Status'), 
                            searchList: { "0": __('未支付'), "1": __('已支付'), "2": __('已发货'), "3": __('已收货'), "4": __('已完成'), "-1": __('仅退款'), "-2": __('退款退货'), "-3": __('售后中'), "-4": __('退货成功'), "-5": __('退货失败') }, 
                            operate: 'LIKE', 
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
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'info',
                                    title: '订单详情',
                                    extend: 'data-toggle="tooltip"',
                                    classname: "btn btn-xs btn-primary btn-dialog",
                                    icon: 'fa fa-eye',
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                },
                                {
                                    name: 'deliver',
                                    title: '发货',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    url: $.fn.bootstrapTable.defaults.extend.deliver_url,
                                    icon: 'fa fa-leaf',
                                    visible: function (row) {

                                        if (row.status == 1) {
                                            return true
                                        }
                                        return false
                                    }
                                },
                                {
                                    name: 'refund',
                                    title: '退货审核',
                                    extend: 'data-toggle="tooltip"',
                                    classname: "btn btn-xs btn-primary btn-dialog",
                                    icon: 'fa fa-check',
                                    url: $.fn.bootstrapTable.defaults.extend.refund_url,
                                    visible: function (row) {
                                        if (row.status == '-1' || row.status == '-2') {
                                            return true;
                                        }

                                        return false;
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        // 订单详情
        info: function () {
            Controller.api.bindevent();
        },

        // 发货
        deliver: function () {
            Controller.api.bindevent();
        },

        // 退货审核
        refund: function () {

            $('#c-refund').change(function () {
                let val = $(this).val();

                if (val == 1) {
                    $('#examinereason').hide();
                    $('#c-examinereason').val('');
                } else {
                    $('#examinereason').show();
                }
            });

            Controller.api.bindevent();
        },

        // 删除
        del: function () {
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
