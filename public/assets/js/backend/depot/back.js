define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'depot/back/index' + location.search,
                    add_url: 'depot/back/add',
                    edit_url: 'depot/back/edit',
                    del_url: 'depot/back/del',
                    info_url: 'depot/back/info',
                    pass_url: 'depot/back/pass',
                    fail_url: 'depot/back/fail',
                    cancel_url: 'depot/back/cancel',
                    receipt_url: 'depot/back/receipt',
                    storage_url: 'depot/back/storage',
                    table: 'depot_back',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                fixedColumns: true,
                fixedRightNumber: 1,
                onLoadSuccess: function () {
                    $('.btn-editone').data('area', ['100%', '100%'])

                    // 给添加按钮添加`data-area`属性
                    $(".btn-add").data("area", ["100%", "100%"]);
                },
                columns: [
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
                        title: __('Code'),
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
                        field: 'business.mobile',
                        title: __('Phone'),
                        operate: 'LIKE'
                    },
                    {
                        field: 'amount',
                        title: __('Amount'),
                        operate: 'BETWEEN'
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
                        field: 'status',
                        title: __('Status'),
                        searchList: { "0": __('未审核'), "1": __('已审核，未收货'), "2": __('已收货，未入库'), "3": __('已入库'), "-1": __('审核不通过') },
                        formatter: Table.api.formatter.status
                    },

                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [
                            {
                                name: 'pass',
                                title: '通过审核',
                                classname: 'btn btn-xs btn-success btn-ajax',
                                icon: 'fa fa-leaf',
                                confirm: '确认通过审核吗？',
                                url: $.fn.bootstrapTable.defaults.extend.pass_url,
                                extend: 'data-toggle="tooltip" data-container="body"',
                                visible: function (row) {
                                    if (row.status == 0) {
                                        return true
                                    }

                                    return false
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                },
                            },
                            {
                                name: 'cancel',
                                title: '撤销审核',
                                classname: 'btn btn-xs btn-danger btn-ajax',
                                icon: 'fa fa-reply',
                                url: $.fn.bootstrapTable.defaults.extend.cancel_url,
                                confirm: '确认要撤销审核吗？',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                visible: function (row) {
                                    if (row.status == 1) {
                                        return true
                                    }

                                    return false
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                }
                            },
                            {
                                name: 'fail',
                                title: '未通过审核',
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-exclamation-triangle',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                confirm: '确认未通过审核吗？',
                                url: $.fn.bootstrapTable.defaults.extend.fail_url,
                                visible: function (row) {
                                    if (row.status == 0) {
                                        return true
                                    }
                                    return false
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                }
                            },
                            {
                                name: 'receipt',
                                title: '确认收货',
                                classname: 'btn btn-xs btn-success btn-ajax',
                                icon: 'fa fa-leaf',
                                confirm: '确认收货吗？',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                url: $.fn.bootstrapTable.defaults.extend.receipt_url,
                                visible: function (row) {
                                    return row.status == 1 ? true : false
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                }
                            },
                            {
                                name: 'storage',
                                title: '确认入库',
                                classname: 'btn btn-xs btn-success btn-ajax',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                icon: 'fa fa-leaf',
                                confirm: '确认入库吗？',
                                url: $.fn.bootstrapTable.defaults.extend.storage_url,
                                visible: function (row) {
                                    return row.status == 2 ? true : false
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                }
                            },
                            {
                                name: 'info',
                                title: '详情',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                url: 'depot/back/info',
                                icon: 'fa fa-eye'
                            },
                            {
                                name: 'edit',
                                title: '编辑',
                                classname: 'btn btn-xs btn-success btn-editone',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                icon: 'fa fa-pencil',
                                url: $.fn.bootstrapTable.defaults.extend.edit_url,
                                visible: function (row) {
                                    if (row.status == 2 || row.status == 3) {
                                        return false
                                    }

                                    return true
                                }
                            },
                            {
                                name: 'del',
                                title: '删除',
                                classname: 'btn btn-xs btn-danger btn-delone',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                icon: 'fa fa-trash',
                                url: $.fn.bootstrapTable.defaults.extend.del_url,
                                visible: function (row) {
                                    if (row.status == 2 || row.status == 3) {
                                        return false
                                    }

                                    return true
                                },
                            }
                        ]
                    }
                ]

            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {

            // 输入框改变事件
            $('#c-ordercode').change(function () {

                // 获得输入的订单号
                var ordercode = $(this).val();

                // 如果订单号为空，返回
                if (!ordercode) {
                    return;
                }

                // 请求订单信息
                $.ajax({
                    url: 'depot/back/order',
                    data: {
                        ordercode: ordercode
                    },
                    dataType: 'json',
                    success: function (res) {

                        // 如果失败，提示错误信息
                        if (res.code != 1) {
                            Toastr.error(res.msg);
                            return;
                        }

                        // 如果请求成功
                        let order = res.data.order;
                        let Consignee_address = res.data.Consignee_address;
                        let order_product = res.data.order_product;
                        let options = '';
                        let tr = '';

                        $('#table').show();

                        for (let item of order_product) {
                            tr += `<tr style="text-align: center; vertical-align: middle; ">`
                            tr += `<td>${item.products.id}</td>`
                            tr += `<td>${item.products.name}</td>`
                            tr += `<td>${item.price}</td>`
                            tr += `<td>${item.pronum}</td>`
                            tr += `<td>${item.total}</td>`
                            tr += `</tr>`
                        }

                        $('#table tbody').html(tr);

                        // 遍历联系人数据
                        for (value of Consignee_address) {
                            options += `<option value="${value.id}" ${value.id == order.businessaddrid ? 'selected="selected"' : ''}>联系人：${value.consignee} 联系方式：${value.mobile} 地址：${value.region_text}-${value.address}</option>`
                        }

                        // 渲染联系人下拉框
                        $('#addrid').html(options);

                        // 刷新下拉框
                        $('#addrid').selectpicker('refresh');
                    }
                });
            });

            $('#table').bootstrapTable({
                columns: [
                    {
                        field: 'id',
                        title: 'ID',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'name',
                        title: __('ProductName'),
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'price',
                        title: __('ProductPrice'),
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'nums',
                        title: __('ProductNum'),
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'total',
                        title: __('ProductAmount'),
                        halign: 'center',
                        valign: 'middle'
                    },
                ]
            })

            $('#table').hide()

            Controller.api.bindevent();
        },

        edit: function () {

            // 输入框改变事件
            $('#c-ordercode').change(function () {

                // 获得输入的订单号
                var ordercode = $(this).val();

                // 如果订单号为空，返回
                if (!ordercode) {
                    return;
                }

                // 请求订单信息
                $.ajax({
                    url: 'depot/back/order',
                    data: {
                        ordercode: ordercode
                    },
                    dataType: 'json',
                    success: function (res) {

                        // 如果失败，提示错误信息
                        if (res.code != 1) {
                            Toastr.error(res.msg);
                            return;
                        }

                        // 如果请求成功
                        let order = res.data.order;
                        let Consignee_address = res.data.Consignee_address;
                        let order_product = res.data.order_product;
                        let options = '';
                        let tr = '';

                        $('#table').show();

                        for (let item of order_product) {
                            tr += `<tr style="text-align: center; vertical-align: middle; ">`
                            tr += `<td>${item.products.id}</td>`
                            tr += `<td>${item.products.name}</td>`
                            tr += `<td>${item.price}</td>`
                            tr += `<td>${item.pronum}</td>`
                            tr += `<td>${item.total}</td>`
                            tr += `</tr>`
                        }

                        $('#table tbody').html(tr);

                        // 遍历联系人数据
                        for (value of Consignee_address) {
                            options += `<option value="${value.id}" ${value.id == order.businessaddrid ? 'selected="selected"' : ''}>联系人：${value.consignee} 联系方式：${value.mobile} 地址：${value.region_text}-${value.address}</option>`
                        }

                        // 渲染联系人下拉框
                        $('#addrid').html(options);

                        // 刷新下拉框
                        $('#addrid').selectpicker('refresh');
                    }
                });
            });

            $('#table').bootstrapTable({
                columns: [
                    {
                        field: 'id',
                        title: 'ID',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'name',
                        title: __('ProductName'),
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'price',
                        title: __('ProductPrice'),
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'nums',
                        title: __('ProductNum'),
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'total',
                        title: __('ProductAmount'),
                        halign: 'center',
                        valign: 'middle'
                    },
                ]
            })
            var BackProductList = Config.back.BackProductList
            let tr = ''

            for (let item of BackProductList) {
                tr += `<tr style="text-align: center; vertical-align: middle; ">`
                tr += `<td>${item.products.id}</td>`
                tr += `<td>${item.products.name}</td>`
                tr += `<td>${item.price}</td>`
                tr += `<td>${item.nums}</td>`
                tr += `<td>${item.total}</td>`
                tr += `</tr>`
            }

            $('#table tbody').html(tr)

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        fail: function () {

            // 给控制器绑定通用事件
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
