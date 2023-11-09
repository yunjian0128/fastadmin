// 定义一个JS控制器 AMD require.js 模块化插件
// 1、需要引入插件
// 2、该控制器模块的业务逻辑
define(['jquery',
    'bootstrap',
    'backend',
    'table',
    'form'],
    function ($,
        undefined,
        Backend,
        Table,
        Form) {

        //定义一个控制器
        var Controller = {
            index: function () {

                // 初始化表格参数配置
                // 配置整个表格中增删查改请求控制器地址，用的ajax的方式请求
                Table.api.init({
                    extend: {
                        index_url: 'depot/supplier/index', // 列表查询的请求控制器方法
                        add_url: 'depot/supplier/add', // 添加的控制器地址
                        edit_url: 'depot/supplier/edit', // 编辑的控制器地址
                        del_url: 'depot/supplier/del', // 删除的控制器地址
                        table: 'depot_supplier',
                    }
                });

                // 获取view视图里面的dom元素table元素
                var table = $("#table")

                // 渲染列表数据
                // $.ajax({
                table.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.index_url, //请求地址
                    toolbar: ".toolbar", // 工具栏
                    pk: 'id', // 默认主键字段名
                    sortName: 'createtime', //排序的字段名
                    sortOrder: 'desc', //排序方式

                    // 渲染的数据部分
                    columns: [ // 渲染的字段部分
                        { checkbox: true },
                        { field: 'id', title: 'ID', operate: false },
                        { field: 'name', title: __('Name') },
                        { field: 'mobile', title: __('Mobile') },
                        {
                            field: 'createtime',
                            title: __('CreateTime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        { field: 'province.name', title: __('Province') },
                        { field: 'city.name', title: __('City') },
                        { field: 'district.name', title: __('District') },

                        { field: 'address', title: __('Address') },

                        // 最后一排的操作按钮组
                        {
                            field: "operate",
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        }
                    ]
                })

                // 为表格绑定事件
                Table.api.bindevent(table);
            },

            add: function () {

                // 选中地区事件处理
                $('#region').on('cp:updated', function () {
                    var citypicker = $(this).data("citypicker");
                    var code = citypicker.getCode("district") || citypicker.getCode("city") || citypicker.getCode("province");
                    $("#region-code").val(code);
                })

                // 给控制器绑定通用事件
                Controller.api.bindevent()
            },

            edit: function () {

                // 选中地区事件处理
                $('#region').on('cp:updated', function () {
                    var citypicker = $(this).data("citypicker");
                    var code = citypicker.getCode("district") || citypicker.getCode("city") || citypicker.getCode("province");
                    $("#region-code").val(code);
                })

                // 给控制器绑定通用事件
                Controller.api.bindevent()
            },

            del: function () {
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
        return Controller
    })