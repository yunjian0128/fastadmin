define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'depot/storage/index' + location.search,
                    add_url: 'depot/storage/add',
                    edit_url: 'depot/storage/edit',
                    del_url: 'depot/storage/del',
                    info_url: 'depot/storage/info',

                    // 撤销审核
                    revoke_url: 'depot/storage/revoke',

                    // 通过审核
                    pass_url: 'depot/storage/pass',

                    // 拒绝审核
                    refuse_url: 'depot/storage/refuse',

                    // 确认入库
                    storage_url: 'depot/storage/storage',
                    table: 'depot_storage',
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

                // 自定义按钮触发的窗口大小
                onLoadSuccess: function () {
                    $('.btn-editone').data('area', ['100%', '100%'])

                    // 给添加按钮添加`data-area`属性
                    $(".btn-add").data("area", ["100%", "100%"]);
                },
                columns: [
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
                        field: 'createtime',
                        title: __('Screatetime'),
                        operate: 'RANGE',
                        addclass: 'datetimerange',
                        autocomplete: false,
                        formatter: Table.api.formatter.datetime
                    },
                    {
                        field: 'status',
                        title: __('Status'),
                        searchList: { "0": __('待审批'), "1": __('审批失败'), "2": __('待入库'), "3": __('入库完成') },
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
                                name: 'revoke',
                                title: '撤销审核',
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-danger btn-ajax",
                                icon: 'fa fa-undo',
                                url: $.fn.bootstrapTable.defaults.extend.revoke_url,
                                confirm: '确认撤销审核？',
                                visible: function (row) {
                                    if (row.status == '1' || row.status == '2') {
                                        return true;
                                    }
                                    return false;
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                }
                            },
                            {
                                name: 'pass',
                                title: '通过审核',
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-success btn-ajax",
                                icon: 'fa fa-check',
                                url: $.fn.bootstrapTable.defaults.extend.pass_url,
                                confirm: '确认通过审核？',
                                visible: function (row) {
                                    if (row.status == '0') {
                                        return true;
                                    }
                                    return false;
                                },
                                success: function (data, ret) {
                                    $(".btn-refresh").trigger("click");
                                },
                                error: function (err) {
                                    console.log(err);
                                }
                            },
                            {
                                name: 'refuse',
                                title: '拒绝审核',
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-danger btn-ajax",
                                icon: 'fa fa-close',
                                url: $.fn.bootstrapTable.defaults.extend.refuse_url,
                                visible: function (row) {
                                    if (row.status == '0') {
                                        return true;
                                    }
                                    return false;
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
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-success btn-ajax",
                                icon: 'fa fa-check',
                                url: $.fn.bootstrapTable.defaults.extend.storage_url,
                                confirm: '确认入库？',
                                visible: function (row) {
                                    if (row.status == '2') {
                                        return true;
                                    }
                                    return false;
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
                                extend: 'data-toggle="tooltip"',
                                classname: "btn btn-xs btn-success btn-dialog",
                                icon: 'fa fa-eye',
                                url: $.fn.bootstrapTable.defaults.extend.info_url,
                            },
                            {
                                name: 'edit',
                                title: '编辑',
                                classname: 'btn btn-xs btn-success btn-editone',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                icon: 'fa fa-pencil',
                                url: 'depot/storage/edit',
                                visible: function (row) {
                                    return row.status == 3 ? false : true
                                }
                            },
                            {
                                name: 'del',
                                title: '删除',
                                classname: 'btn btn-xs btn-danger btn-delone',
                                extend: 'data-toggle="tooltip" data-container="body"',
                                icon: 'fa fa-trash',
                                url: 'depot/storage/del',
                                visible: function (row) {
                                    return row.status == 3 ? false : true
                                }
                            },
                        ]
                    }
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {

            // 选项卡改变事件
            $('#c-supplier').change(function (e) {
                var num = e.target.value;

                if (num) {
                    $.ajax({
                        url: 'depot/storage/supplier',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            num: num
                        },
                        success: function (result) {
                            if (result) {
                                $('#c-mobile').val(result.data.mobile);
                                $('#c-region').val(result.data.address);
                            }
                        },
                        error: function (err) {
                            console.log(err);
                        }
                    });
                }
            });

            // 初始化商品表格
            $('#table').bootstrapTable({
                columns: [
                    {
                        field: 'id',
                        title: 'ID',
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'name',
                        title: __("Proname"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'unit',
                        title: __("Unitid"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'price',
                        title: __("Proprice"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'nums',
                        title: __("Proamount"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'subtotal',
                        title: __("Prototal"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'typeid',
                        title: __("Typeid"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'operate',
                        title: __('Operate'),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                ]
            });

            // 选择的商品数据
            let list = [];

            // 提交的商品数据
            let ProductList = [];

            // 存放商品id
            let ProidList = [];

            // 添加商品按钮事件
            $('#product').on('click', function () {
                Backend.api.open('depot/storage/product', __('SelectProduct'), {
                    area: ['80%', '80%'],
                    callback: function (data) {

                        if (data) {
                            // console.log(data);
                            // return false;

                            // 判断是不是二维数组
                            if (!Array.isArray(data)) {
                                data = [data];
                            }

                            // 渲染HTML
                            let tr = ''

                            // 遍历商品数据
                            for (let item of data) {

                                // 判断是否已经添加过该商品
                                if (ProidList.includes(item.id)) {
                                    Backend.api.msg(`${item.name}商品已经添加过了`);
                                    continue;
                                }

                                list.push(item);

                                tr += `<tr style="text-align: center; vertical-align: middle; " data-index="0">`
                                tr += `<td>${item.id}</td>`
                                tr += `<td>${item.name}</td>`
                                tr += `<td>${item.unit.name}</td>`
                                tr += `<td><input name=row[price] class="price" type="number" min="0" /></td>`
                                tr += `<td><input name=row[nums] class="nums" type="number" min="0" /></td>`
                                tr += `<td class="subtotal"></td>`
                                tr += `<td>${item.category.name}</td>`
                                tr += `<td>
                                        <button class="btn btn-primary btn-xs ProAdd">添加</button>
                                        <button class="btn btn-danger btn-xs ProDel">删除</button>
                                    </td>`
                                tr += `</tr>`

                                ProidList.push(item.id);
                            }

                            tr += `<tr id="count" style="text-align: center; vertical-align: middle; ">`
                            tr += `<td>合计</td>`
                            tr += `<td></td>`
                            tr += `<td></td>`
                            tr += `<td></td>`
                            tr += `<td class="product-nums">0</td>`
                            tr += `<td class="total">0</td>`
                            tr += `<td></td>`
                            tr += `<td></td>`
                            tr += `</tr>`

                            // 移除按钮
                            $('#product').css('display', 'none');

                            // 渲染表格
                            $('#table tbody').html(tr);
                        }
                    }
                });
            });

            // 从表格删除商品
            $('#table').on('click', '.ProDel', function () {
                let index = $(this).parents('tr').data('index');

                // 删除本行
                $(this).parents('tr').remove();

                // 删除数据
                list.splice(index, 1);

                // 如果没有数据了，显示添加按钮
                if (list.length == 0) {

                    // 移除统计行
                    $('#count').remove();

                    // 提示没有数据
                    var tr = `<tr class="no-records-found"><td colspan="9">没有找到匹配的记录</td></tr>`;
                    $('#table tbody').html(tr);

                    // 显示添加按钮
                    $('#product').css('display', 'inline-block');
                }

                // 删除商品数据
                ProductList.splice(index, 1);

                // 从商品id数据移除选择删除商品，方便后面还可以添加回来该商品
                ProidList.splice(index, 1);

                // 更新隐藏域数据
                $('#products').val(JSON.stringify(ProductList));

                var subtotal = $('.subtotal');

                var total = 0;

                for (let item of subtotal) {
                    total += item.innerText ? parseFloat(item.innerText) : 0
                }

                // 更新隐藏域合计
                $('#total').val(total);

                // 更新合计
                $('.total').text(total.toFixed(2));

                // 更新数量
                var nums = $('.nums');
                var ProductNums = 0;

                for (let item of nums) {
                    ProductNums += item.value ? parseInt(item.value) : 0
                }

                // 显示数量
                $('.product-nums').text(ProductNums);

                return false;
            });

            // 从表格添加商品
            $('#table').on('click', '.ProAdd', function () {

                // 获取当前行索引
                let index = $(this).parents('tr').data('index');

                // 获取表格添加商品的按钮的父级data属性
                let parent = $(this).parents('tr');

                // 打开一个新窗口
                Backend.api.open('depot/storage/product', __('SelectProduct'), {

                    // 设置窗口大小
                    area: ['80%', '80%'],
                    callback: function (data) {

                        // 判断是不是二维数组
                        if (!Array.isArray(data)) {
                            data = [data];
                        }

                        // 渲染HTML
                        let tr = '';

                        // 遍历商品数据
                        for (let item of data) {

                            // 判断是否已经添加过该商品
                            if (ProidList.includes(item.id)) {
                                Backend.api.msg(`${item.name}商品已经添加过了`);
                                continue;
                            }

                            // 更新表格数据
                            tr = `
                            <tr style="text-align: center; vertical-align: middle; " data-index="${index + 1}">
                                    <td class="proid">${item.id}</td>
                                    <td>${item.name}</td>
                                    <td>${item.unit.name}</td>
                                    <td><input name="row[price]" class="price" type="number" min="1" required /></td>
                                    <td><input name="row[nums]" class="nums" type="number" min="1" required /></td>
                                    <td class="subtotal"></td>
                                    <td>${item.category.name}</td>
                                    <td>
                                        <button class="btn btn-primary btn-xs ProAdd">添加</button>
                                        <button class="btn btn-danger btn-xs ProDel">删除</button>
                                    </td>
                                </tr>
                        `;

                            // 更新商品数据
                            list.push(item);

                            // 更新商品数据
                            ProidList.push(item.id);

                            // 更新表格
                            parent.after(tr);
                        }
                    }
                });

                return false;
            });

            // 单价输入框事件
            $('#table').on('blur', '.price', function () { // 失去焦点事件 blur 事件会在元素失去焦点时发生。

                // 清除错误提示
                $(this).next().remove();

                // 判断单价是否为空
                if (!$.trim($(this).val())) {
                    $(this).after(`<span style="color:red;margin-left:5px;">此处不能为空</span>`)
                    return false;
                }

                // 如果输入的值为0，提示错误
                if ($(this).val() == 0) {
                    $(this).after(`<span style="color:red;margin-left:5px;">单价不能为0</span>`)
                    return false;
                }

                // 获取单价
                let price = $(this).val() ? parseFloat($(this).val()) : 0;

                // 获取相邻的数量
                var num = $(this).parent('td').next().find('input').val() ? parseInt($(this).parent('td').next().find('input').val()) : 0;

                // 计算小计
                let Price = price * num;

                // 获取当前的商品id
                var proid = $(this).parent().prev().prev().prev().text();

                if (Price >= 0) {

                    // 更新小计
                    $(this).parent('td').next().next().text(Price.toFixed(2));

                    // 计算每种商品数量
                    var nums = $('.nums');

                    var ProductNums = 0;

                    for (let item of nums) {
                        ProductNums += item.value ? parseInt(item.value) : 0
                    }

                    // 显示数量
                    $('.product-nums').text(ProductNums);

                    // 计算合计
                    var subtotal = $('.subtotal');

                    var total = 0;

                    for (let item of subtotal) {
                        total += item.innerText ? parseFloat(item.innerText) : 0
                    }

                    // 更新隐藏域合计
                    $('#total').val(total);

                    // 更新合计
                    $('.total').text(total.toFixed(2));

                    if (ProidList.includes(proid)) {
                        for (let item of ProductList) {
                            if (proid == item.id) {
                                item.price = price
                                item.nums = num
                                item.total = Price
                            }
                        }
                    } else {
                        ProductList.push({
                            id: proid,
                            price: price,
                            nums: num,
                            total: Price // 这个是单价*数量
                        })

                        ProidList.push(proid)
                    }

                    // 更新隐藏域数据
                    $('#products').val(JSON.stringify(ProductList));
                }
            });

            // 数量输入框事件
            $('#table').on('blur', '.nums', function () {
                $(this).next().remove();

                if (!$.trim($(this).val())) {
                    $(this).after(`<span style="color:red;margin-left:5px;">此处不能为空</span>`)
                    return false;
                }

                if ($(this).val() == 0) {
                    $(this).after(`<span style="color:red;margin-left:5px;">数量不能为0</span>`)
                    return false;
                }

                // 获取相邻的单价
                var price = $(this).parent('td').prev().find('input').val() ? parseFloat($(this).parent('td').prev().find('input').val()) : 0;

                // 获取当前的商品id
                var proid = $(this).parent().prev().prev().prev().prev().text();

                // 获取数量
                let num = $(this).val() ? parseInt($(this).val()) : 0;

                // 计算小计
                let Price = price * num;

                if (Price >= 0) {

                    // 更新小计
                    $(this).parent('td').next().text(Price.toFixed(2));

                    // 计算每种商品数量
                    var nums = $('.nums');

                    var ProductNums = 0;

                    for (let item of nums) {
                        ProductNums += item.value ? parseInt(item.value) : 0
                    }

                    // 显示数量
                    $('.product-nums').text(ProductNums);

                    // 计算合计
                    var subtotal = $('.subtotal');

                    var total = 0;

                    for (let item of subtotal) {
                        total += item.innerText ? parseFloat(item.innerText) : 0
                    }

                    // 更新隐藏域合计
                    $('#total').val(total);

                    // 更新合计
                    $('.total').text(total.toFixed(2));

                    if (ProidList.includes(proid)) {
                        for (let item of ProductList) {
                            if (proid == item.id) {
                                item.price = price
                                item.nums = num
                                item.total = Price
                            }
                        }

                    } else {
                        ProductList.push({
                            id: proid,
                            price: price,
                            nums: num,
                            total: Price // 这个是单价*数量
                        })

                        ProidList.push(proid)
                    }

                    // 更新隐藏域数据
                    $('#products').val(JSON.stringify(ProductList));
                }
            });

            Controller.api.bindevent();
        },

        // 添加商品表格
        product: function () {
            // 初始化表格参数配置
            Table.api.init({});

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'depot/storage/product',
                pk: 'id',
                sortName: 'product.id',
                columns: [
                    [
                        {
                            checkbox: true
                        },
                        { field: 'id', title: 'ID', sortable: true },
                        { field: 'name', title: __('Name'), operate: 'LIKE' },
                        { field: 'status', title: __('Flag'), searchList: { "0": __('下架'), "1": __('上架') }, formatter: Table.api.formatter.flag },
                        { field: 'stock', title: __('Stock') },
                        { field: 'category.name', title: __('Typeid') },
                        { field: 'unit.name', title: __('Unitid') },
                        { field: 'createtime', title: __('Createtime'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'chapter', text: '选择',
                                    title: '选择',
                                    classname: 'btn btn-primary btn-xs product',
                                },
                            ]
                        }
                    ]
                ]
            });

            // 单个商品选择
            $('#table').on('click', '.product', function () {
                let index = $(this).parents('tr').data('index') // 获取当前行索引

                let data = Table.api.getrowdata(table, index);// 获取当前行数据

                Backend.api.close(data)
            });

            // 多个商品选择
            $('#product').on('click', function () {
                let data = [];

                // 获取复选框被选中的个数
                let len = $('.selected').length;

                // 遍历复选框 获取选中的数据
                for (let i = 0; i < len; i++) {
                    let index = $('.selected').eq(i).data('index');
                    data.push(Table.api.getrowdata(table, index));
                }
                Backend.api.close(data)
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        edit: function () {

            // 选项卡改变事件
            $('#c-supplier').change(function (e) {
                var num = e.target.value;

                if (num) {
                    $.ajax({
                        url: 'depot/storage/supplier',
                        type: 'post',
                        dataType: 'json',
                        data: {
                            num: num
                        },
                        success: function (result) {
                            if (result) {
                                $('#c-mobile').val(result.data.mobile);
                                $('#c-region').val(result.data.address);
                            }
                        },
                        error: function (err) {
                            console.log(err);
                        }
                    });
                }
            });

            // 初始化商品表格
            $('#table').bootstrapTable({
                columns: [
                    {
                        field: 'id',
                        title: 'ID',
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'name',
                        title: __("Proname"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'unit',
                        title: __("Unitid"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'price',
                        title: __("Proprice"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'nums',
                        title: __("Proamount"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'subtotal',
                        title: __("Prototal"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'typeid',
                        title: __("Typeid"),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                    {
                        field: 'operate',
                        title: __('Operate'),
                        align: 'center',
                        halign: 'center',
                        valign: 'middle'
                    },
                ]
            });

            // 获取php传过来的数据
            var ProductData = Config.ProductData;

            // 添加商品的数据
            var list = []

            // 存放商品的数量单价数据
            var ProductList = []

            // 存放商品的数量单价id
            var ProidList = []

            // 定义删除时，把已在数据库的商品追加到这里
            var DelIdList = []

            if (ProductData) {

                let tr = ''
                let index = 0
                let TotalCount = 0
                let NumCount = 0

                for (let item of ProductData) {
                    tr += `<tr data-index="${index++}" style="text-align: center; vertical-align: middle; ">`
                    tr += `<td>${item.product.id}</td>`
                    tr += `<td>${item.product.name}</td>`
                    tr += `<td>${item.product.unit_text}</td>`
                    tr += `<td><input name="row[price]" class="price" type="number" min="0" value="${item.price}" /></td>`
                    tr += `<td><input name="row[nums]" class="nums" type="number" min="0" value="${item.nums}" /></td>`
                    tr += `<td class="subtotal">${item.total}</td>`
                    tr += `<td>${item.product.category_text}</td>`
                    tr += `<td>
                                <button class="btn btn-primary btn-xs ProAdd">添加</button>
                                <button class="btn btn-danger btn-xs ProDel">删除</button>
                            </td>`
                    tr += `</tr>`
                    tr += `<tr id="count">`
                    list.push(item.product)
                    ProidList.push(item.product.id)

                    ProductList.push({
                        id: item.id,
                        proid: item.product.id,
                        price: item.price,
                        nums: item.nums.toString(),
                        total: item.total // 这个是单价*数量
                    })

                    TotalCount += item.total ? parseFloat(item.total) : 0
                    NumCount += item.nums ? parseInt(item.nums) : 0
                }

                tr += `<td>合计</td>`
                tr += `<td></td>`
                tr += `<td></td>`
                tr += `<td></td>`
                tr += `<td class="product-nums">${NumCount}</td>`
                tr += `<td class="total">${TotalCount.toFixed(2)}</td>`
                tr += `<td></td>`
                tr += `<td></td>`
                tr += `</tr>`

                $('#products').val(JSON.stringify(ProductList))
                $('#product').css({ 'display': 'none' })
                $('#table tbody').html(tr)
            };

            // 添加商品按钮事件
            $('#product').on('click', function () {
                Backend.api.open('depot/storage/product', __('SelectProduct'), {
                    area: ['80%', '80%'],
                    callback: function (data) {

                        if (data) {
                            // console.log(data);
                            // return false;

                            // 判断是不是二维数组
                            if (!Array.isArray(data)) {
                                data = [data];
                            }

                            // 渲染HTML
                            let tr = ''

                            // 遍历商品数据
                            for (let item of data) {

                                // 判断是否已经添加过该商品
                                if (ProidList.includes(item.id)) {
                                    Backend.api.msg(`${item.name}商品已经添加过了`);
                                    continue;
                                }

                                list.push(item);

                                tr += `<tr style="text-align: center; vertical-align: middle; " data-index="0">`
                                tr += `<td>${item.id}</td>`
                                tr += `<td>${item.name}</td>`
                                tr += `<td>${item.unit.name}</td>`
                                tr += `<td><input name=row[price] class="price" type="number" min="0" /></td>`
                                tr += `<td><input name=row[nums] class="nums" type="number" min="0" /></td>`
                                tr += `<td class="subtotal"></td>`
                                tr += `<td>${item.category.name}</td>`
                                tr += `<td>
                                        <button class="btn btn-primary btn-xs ProAdd">添加</button>
                                        <button class="btn btn-danger btn-xs ProDel">删除</button>
                                    </td>`
                                tr += `</tr>`

                                ProidList.push(item.id);
                            }

                            tr += `<tr id="count" style="text-align: center; vertical-align: middle; ">`
                            tr += `<td>合计</td>`
                            tr += `<td></td>`
                            tr += `<td></td>`
                            tr += `<td></td>`
                            tr += `<td class="product-nums">0</td>`
                            tr += `<td class="total">0</td>`
                            tr += `<td></td>`
                            tr += `<td></td>`
                            tr += `</tr>`

                            // 移除按钮
                            $('#product').css('display', 'none');

                            // 渲染表格
                            $('#table tbody').html(tr);
                        }
                    }
                });
            });

            // 从表格删除商品
            $('#table').on('click', '.ProDel', function () {

                if (list.length == 1) {
                    Backend.api.msg('这是最后一件商品')
                    return false
                }

                let index = $(this).parents('tr').data('index');

                // 删除本行
                $(this).parents('tr').remove();

                // 删除数据
                list.splice(index, 1);

                // 追加数组
                DelIdList.push(ProductList[index].id)

                // 如果没有数据了，显示添加按钮
                if (list.length == 0) {

                    // 移除统计行
                    $('#count').remove();

                    // 提示没有数据
                    var tr = `<tr class="no-records-found"><td colspan="9">没有找到匹配的记录</td></tr>`;
                    $('#table tbody').html(tr);

                    // 显示添加按钮
                    $('#product').css('display', 'inline-block');
                }

                // 删除商品数据
                ProductList.splice(index, 1);

                // 从商品id数据移除选择删除商品，方便后面还可以添加回来该商品
                ProidList.splice(index, 1);

                // 更新隐藏域数据
                $('#products').val(JSON.stringify(ProductList));

                var subtotal = $('.subtotal');

                var total = 0;

                for (let item of subtotal) {
                    total += item.innerText ? parseFloat(item.innerText) : 0
                }

                // 更新隐藏域合计
                $('#total').val(total);

                // 更新合计
                $('.total').text(total.toFixed(2));

                // 更新数量
                var nums = $('.nums');
                var ProductNums = 0;

                for (let item of nums) {
                    ProductNums += item.value ? parseInt(item.value) : 0
                }

                // 显示数量
                $('.product-nums').text(ProductNums);

                // 更新删除的商品id
                $('#delproid').val(JSON.stringify(DelIdList));
                return false;
            });

            // 从表格添加商品
            $('#table').on('click', '.ProAdd', function () {

                // 获取当前行索引
                let index = $(this).parents('tr').data('index');

                // 获取表格添加商品的按钮的父级data属性
                let parent = $(this).parents('tr');

                // 打开一个新窗口
                Backend.api.open('depot/storage/product', __('SelectProduct'), {

                    // 设置窗口大小
                    area: ['80%', '80%'],
                    callback: function (data) {

                        // 判断是不是二维数组
                        if (!Array.isArray(data)) {
                            data = [data];
                        }

                        // 渲染HTML
                        let tr = '';

                        // 遍历商品数据
                        for (let item of data) {

                            // 判断是否已经添加过该商品
                            if (ProidList.includes(item.id)) {
                                Backend.api.msg(`${item.name}商品已经添加过了`);
                                continue;
                            }

                            // 更新表格数据
                            tr = `
                            <tr style="text-align: center; vertical-align: middle; " data-index="${index + 1}">
                                    <td class="proid">${item.id}</td>
                                    <td>${item.name}</td>
                                    <td>${item.unit.name}</td>
                                    <td><input name="row[price]" class="price" type="number" min="1" required /></td>
                                    <td><input name="row[nums]" class="nums" type="number" min="1" required /></td>
                                    <td class="subtotal"></td>
                                    <td>${item.category.name}</td>
                                    <td>
                                        <button class="btn btn-primary btn-xs ProAdd">添加</button>
                                        <button class="btn btn-danger btn-xs ProDel">删除</button>
                                    </td>
                            </tr>
                        `;

                            // 更新商品数据
                            list.push(item);

                            // 更新商品数据
                            ProidList.push(item.id);

                            // 更新表格
                            parent.after(tr);
                        }
                    }
                });

                return false;
            });

            // 单价输入框事件
            $('#table').on('blur', '.price', function () { // 失去焦点事件 blur 事件会在元素失去焦点时发生。

                // 清除错误提示
                $(this).next().remove();

                // 判断单价是否为空
                if (!$.trim($(this).val())) {
                    $(this).after(`<span style="color:red;margin-left:5px;">此处不能为空</span>`)
                    return false;
                }

                // 如果输入的值为0，提示错误
                if ($(this).val() == 0) {
                    $(this).after(`<span style="color:red;margin-left:5px;">单价不能为0</span>`)
                    return false;
                }

                // 获取单价
                let price = $(this).val() ? parseFloat($(this).val()) : 0;

                // 获取相邻的数量
                var num = $(this).parent('td').next().find('input').val() ? parseInt($(this).parent('td').next().find('input').val()) : 0;

                // 计算小计
                let Price = price * num;

                // 获取当前的商品id
                var proid = $(this).parent().prev().prev().prev().text();

                if (Price >= 0) {

                    // 更新小计
                    $(this).parent('td').next().next().text(Price.toFixed(2));

                    // 计算每种商品数量
                    var nums = $('.nums');

                    var ProductNums = 0;

                    for (let item of nums) {
                        ProductNums += item.value ? parseInt(item.value) : 0
                    }

                    // 显示数量
                    $('.product-nums').text(ProductNums);

                    // 计算合计
                    var subtotal = $('.subtotal');

                    var total = 0;

                    for (let item of subtotal) {
                        total += item.innerText ? parseFloat(item.innerText) : 0
                    }

                    // 更新隐藏域合计
                    $('#total').val(total);

                    // 更新合计
                    $('.total').text(total.toFixed(2));

                    if (ProidList.includes(proid)) {
                        for (let item of ProductList) {
                            if (proid == item.id) {
                                item.price = price
                                item.nums = num
                                item.total = Price
                            }
                        }
                    } else {
                        ProductList.push({
                            id: proid,
                            price: price,
                            nums: num,
                            total: Price // 这个是单价*数量
                        })

                        ProidList.push(proid)
                    }

                    // 更新隐藏域数据
                    $('#products').val(JSON.stringify(ProductList));
                }
            });

            // 数量输入框事件
            $('#table').on('blur', '.nums', function () {
                $(this).next().remove();

                if (!$.trim($(this).val())) {
                    $(this).after(`<span style="color:red;margin-left:5px;">此处不能为空</span>`)
                    return false;
                }

                if ($(this).val() == 0) {
                    $(this).after(`<span style="color:red;margin-left:5px;">数量不能为0</span>`)
                    return false;
                }

                // 获取相邻的单价
                var price = $(this).parent('td').prev().find('input').val() ? parseFloat($(this).parent('td').prev().find('input').val()) : 0;

                // 获取当前的商品id
                var proid = $(this).parent().prev().prev().prev().prev().text();

                // 获取数量
                let num = $(this).val() ? parseInt($(this).val()) : 0;

                // 计算小计
                let Price = price * num;

                if (Price >= 0) {

                    // 更新小计
                    $(this).parent('td').next().text(Price.toFixed(2));

                    // 计算每种商品数量
                    var nums = $('.nums');

                    var ProductNums = 0;

                    for (let item of nums) {
                        ProductNums += item.value ? parseInt(item.value) : 0
                    }

                    // 显示数量
                    $('.product-nums').text(ProductNums);

                    // 计算合计
                    var subtotal = $('.subtotal');

                    var total = 0;

                    for (let item of subtotal) {
                        total += item.innerText ? parseFloat(item.innerText) : 0
                    }

                    // 更新隐藏域合计
                    $('#total').val(total);

                    // 更新合计
                    $('.total').text(total.toFixed(2));

                    if (ProidList.includes(proid)) {
                        for (let item of ProductList) {
                            if (proid == item.id) {
                                item.price = price
                                item.nums = num
                                item.total = Price
                            }
                        }

                    } else {
                        ProductList.push({
                            id: proid,
                            price: price,
                            nums: num,
                            total: Price // 这个是单价*数量
                        })

                        ProidList.push(proid)
                    }

                    // 更新隐藏域数据
                    $('#products').val(JSON.stringify(ProductList));
                }
            });

            Controller.api.bindevent();
        },

        // 撤销审核
        revoke: function () {
            Controller.api.bindevent();
        },

        // 通过审核
        pass: function () {
            Controller.api.bindevent();
        },

        // 拒绝审核
        refuse: function () {
            Controller.api.bindevent();
        },

        // 确认入库
        confirm: function () {
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

        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };

    return Controller;
});
