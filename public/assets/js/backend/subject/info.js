define([
    'jquery',
    'bootstrap',
    'backend',
    'table',
    'form',
], function ($,
    undefined,
    Backend,
    Table,
    Form) {
    // 定义一个控制器
    var Controller = {
        index: function () {

            // 给选项卡绑定事件 切换选项卡的时候触发
            $(`a[data-toggle="tab"]`).on('shown.bs.tab', function () {

                // 获取当前选中的选项卡的id
                // var tab = $(e.target).attr('href');
                var tab = $($(this).attr('href'))
                if (tab.length <= 0) return; // 为空就停下

                // console.log(tab);

                // 两个选项卡，分别给两个不同的请求方法
                // 根据锚点切换，切换的时候触发选项卡的事件
                Controller.table[tab.attr('id')].call(this);
            });

            // 触发第一个选项卡的事件
            Controller.table['order']();
        },

        table: {

            // 课程订单
            order: function () {

                // 接收参数
                var ids = Fast.api.query('ids');

                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        order_url: `subject/info/order?ids=${ids}`, // 订单的地址
                        table: 'subject_order', // 表名
                    }
                });

                // 获取view视图里面的表格
                var OrderTable = $("#OrderTable");

                // 渲染列表数据
                // $.ajax({
                OrderTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.order_url, // 请求地址
                    toolbar: '#OrderToolbar', // 工具栏
                    pk: 'id', // 主键
                    sortName: 'createtime', // 排序字段
                    sortOrder: 'desc', // 排序方式

                    // 渲染的数据
                    columns: [
                        { field: 'id', title: 'ID', operate: false, sortable: true },
                        { field: 'code', title: __('OrderCode'), operate: 'LIKE' },
                        { field: 'total', title: __('OrderTotal'), operate: false, sortable: true },
                        { field: 'business.nickname', title: __('BusinessNickname'), operate: 'LIKE' },
                        {
                            field: 'createtime',
                            title: __('OrderTime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',  // 添加class
                            sortable: true,
                            formatter: Table.api.formatter.datetime
                        }
                    ],
                })

                // 为表格绑定事件
                Table.api.bindevent(OrderTable);
            },

            // 课程评论
            comment: function () {

                // 接收参数
                var ids = Fast.api.query('ids');

                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        comment_url: `subject/info/comment?ids=${ids}`, // 评论的地址
                        table: 'subject_comment', // 表名
                    }
                });

                // 获取view视图里面的表格
                var CommentTable = $("#CommentTable");

                // 渲染列表数据
                // $.ajax({
                CommentTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.comment_url, // 请求地址
                    toolbar: '#CommentToolbar', // 工具栏
                    pk: 'id', // 主键
                    sortName: 'createtime', // 排序字段
                    sortOrder: 'desc', // 排序方式

                    // 渲染的数据
                    columns: [
                        { field: 'id', title: 'ID', operate: false, sortable: true },
                        { field: 'business.nickname', title: __('BusinessNickname'), operate: 'LIKE' },
                        { field: 'content', title: __('CommentContent'), operate: 'LIKE' },
                        {
                            field: 'createtime',
                            title: __('CommentTime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true,
                            formatter: Table.api.formatter.datetime
                        },
                    ]
                });

                // 为表格绑定事件
                Table.api.bindevent(CommentTable);
            },

            // 课程章节
            chapter: function () {

                // 接收参数
                var ids = Fast.api.query('ids');

                // 初始化表格参数配置
                // 配置整个表格中增删改查请求控制器的地址，用Ajax请求
                Table.api.init({
                    extend: {
                        chapter_url: `subject/info/chapter?ids=${ids}`, // 章节的地址
                        add_url: `subject/info/chapter_add?ids=${ids}`, // 添加章节的地址
                        edit_url: `subject/info/chapter_edit`, // 编辑章节的地址
                        del_url: `subject/info/chapter_del`, // 删除章节的地址
                        table: `subject_chapter`, // 表名
                    }
                });

                // 获取view视图里面的表格
                var ChapterTable = $("#ChapterTable");

                // 渲染列表数据
                // $.ajax({
                ChapterTable.bootstrapTable({
                    url: $.fn.bootstrapTable.defaults.extend.chapter_url, // 章节的地址
                    toolbar: '#ChapterToolbar', // 工具栏
                    pk: 'id', // 主键
                    sortName: 'createtime', // 排序字段
                    sortOrder: 'desc', // 排序方式

                    // 渲染的数据
                    columns: [
                        { checkbox: true },
                        { field: 'id', title: 'ID', operate: false, sortable: true },
                        { field: 'title', title: __('Title'), operate: 'LIKE' },
                        { field: 'url', title: __('Url'), operate: false, sortable: false, formatter: Table.api.formatter.file },
                        {
                            field: 'createtime',
                            title: __('ChapterTime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true,
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: ChapterTable,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                        }
                    ]
                })

                // 为表格绑定事件
                Table.api.bindevent(ChapterTable);
            }
        },

        // 课程订单
        order: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 课程评论
        comment: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 课程章节
        chapter: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 添加课程章节
        chapter_add: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 编辑课程章节
        chapter_edit: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        // 删除课程章节
        chapter_del: function () {

            // 给控制器绑定通用事件
            Controller.api.bindevent();
        },

        api: {

            // JS模块化的全局方法
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    }

    // 模块返回值
    return Controller;

});