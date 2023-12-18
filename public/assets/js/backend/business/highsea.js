// 定义一个JS控制器 AMD require.js 模块化插件
// 1、需要引入插件
// 2、该控制器模块的业务逻辑
define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    //定义一个控制器
    var Controller = {
        index: function () {

            // 初始化表格参数配置
            // 配置整个表格中增删查改请求控制器地址，用的ajax的方式请求
            Table.api.init({
                extend: {
                    index_url: 'business/highsea/index', // 列表查询的请求控制器方法
                    apply_url: 'business/highsea/apply', // 领取的控制器地址
                    recovery_url: 'business/highsea/recovery', // 分配的控制器地址
                    del_url: 'business/highsea/del', // 删除的控制器地址
                    table: 'business',
                }
            });

            // 获取view视图里面的dom元素table元素
            var table = $("#table")

            // 渲染列表数据
            // $.ajax({
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url, // 请求地址
                toolbar: ".toolbar", // 工具栏
                pk: 'id', // 默认主键字段名
                sortName: 'createtime', // 排序的字段名
                sortOrder: 'desc', // 排序方式

                // 渲染的数据部分
                columns: [
                    { checkbox: true },
                    {
                        field: 'id',
                        title: 'ID',
                        operate: false,
                        sortable: true
                    },
                    {
                        field: 'nickname',
                        title: __('Nickname')
                    },
                    {
                        field: 'source.name',
                        title: __('Source')
                    },
                    {
                        field: 'gender',
                        title: __('Gender'),
                        searchList: { "0": __('保密'), "1": __('男'), "2": __('女') },
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'deal',
                        title: __('Deal'),
                        searchList: { "0": __('未成交'), "1": __('已成交') },
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'auth',
                        title: __('Auth'),
                        searchList: { "0": __('未认证'), "1": __('已认证') },
                        formatter: Table.api.formatter.normal
                    },
                    {
                        field: 'operate',
                        title: __('Operate'),
                        table: table,
                        events: Table.api.events.operate,
                        formatter: Table.api.formatter.operate,
                        buttons: [
                            {
                                name: 'apply', // 跟table页面中绑定一样
                                title: '领取',
                                icon: 'fa fa-arrow-down', // 图标
                                classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                url: $.fn.bootstrapTable.defaults.extend.apply_url,
                                extend: "data-toggle='tooltip'", // 额外的属性
                                confirm: "是否确认领取数据",
                                success: function () {

                                    // 刷新表格
                                    table.bootstrapTable('refresh')
                                }
                            },

                            {
                                name: 'recovery',
                                title: '分配',
                                icon: 'fa fa-arrows-h',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                url: $.fn.bootstrapTable.defaults.extend.recovery_url,
                                extend: 'data-toggle=\'tooltip\' data-area= \'["50%", "50%"]\'',
                                success: function () {

                                    // 刷新表格
                                    table.bootstrapTable('refresh')
                                }
                            }
                        ]
                    }
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 领取
            $(".btn-apply").on("click", function () {

                // 获取选中的id
                var ids = Table.api.selectedids(table);

                // 弹框
                layer.confirm(
                    '是否确认领取数据',
                    { title: "领取标题", btn: ['是', '否'] },
                    function (index) {

                        // 发送ajax请求
                        Backend.api.ajax(
                            { url: $.fn.bootstrapTable.defaults.extend.apply_url + `?ids=${ids}` },
                            () => {
                                // 关闭弹框
                                layer.close(index)

                                // 刷新表格
                                table.bootstrapTable('refresh')
                            }
                        )
                    }
                )
            });

            // 分配
            $(".btn-recovery").on("click", function () {

                // 获取选中的id
                var ids = Table.api.selectedids(table);

                // 转到分配页面
                Fast.api.open($.fn.bootstrapTable.defaults.extend.recovery_url + `?ids=${ids}`, '分配', {
                    callback: function () {

                        // 刷新表格
                        table.bootstrapTable('refresh')
                    }
                })
            });
        },

        // 领取
        apply: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 分配
        recovery: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 删除
        del: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        api: {

            // JS模块化的全局方法
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        },
    };

    return Controller;
})