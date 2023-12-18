<?php

// 公共助手函数

use think\exception\HttpResponseException;
use think\Response;

if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array  $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name) {
            return $name;
        }
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return \think\Lang::get($name, $vars, $lang);
    }
}

if (!function_exists('format_bytes')) {

    /**
     * 将字节转换为可读文本
     * @param int    $size      大小
     * @param string $delimiter 分隔符
     * @param int    $precision 小数位数
     * @return string
     */
    function format_bytes($size, $delimiter = '', $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 6; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . $delimiter . $units[$i];
    }
}

if (!function_exists('datetime')) {

    /**
     * 将时间戳转换为日期时间
     * @param int    $time   时间戳
     * @param string $format 日期时间格式
     * @return string
     */
    function datetime($time, $format = 'Y-m-d H:i:s')
    {
        $time = is_numeric($time) ? $time : strtotime($time);
        return date($format, $time);
    }
}

if (!function_exists('human_date')) {

    /**
     * 获取语义化时间
     * @param int $time  时间
     * @param int $local 本地时间
     * @return string
     */
    function human_date($time, $local = null)
    {
        return \fast\Date::human($time, $local);
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string  $url    资源相对地址
     * @param boolean $domain 是否显示域名 或者直接传入域名
     * @return string
     */
    function cdnurl($url, $domain = false)
    {
        $regex = "/^((?:[a-z]+:)?\/\/|data:image\/)(.*)/i";
        $cdnurl = \think\Config::get('upload.cdnurl');
        if (is_bool($domain) || stripos($cdnurl, '/') === 0) {
            $url = preg_match($regex, $url) || ($cdnurl && stripos($url, $cdnurl) === 0) ? $url : $cdnurl . $url;
        }
        if ($domain && !preg_match($regex, $url)) {
            $domain = is_bool($domain) ? request()->domain() : $domain;
            $url = $domain . $url;
        }
        return $url;
    }
}

if (!function_exists('is_really_writable')) {

    /**
     * 判断文件或文件夹是否可写
     * @param string $file 文件或目录
     * @return    bool
     */
    function is_really_writable($file)
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return is_writable($file);
        }
        if (is_dir($file)) {
            $file = rtrim($file, '/') . '/' . md5(mt_rand());
            if (($fp = @fopen($file, 'ab')) === false) {
                return false;
            }
            fclose($fp);
            @chmod($file, 0777);
            @unlink($file);
            return true;
        } elseif (!is_file($file) or ($fp = @fopen($file, 'ab')) === false) {
            return false;
        }
        fclose($fp);
        return true;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname  目录
     * @param bool   $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('copydirs')) {

    /**
     * 复制文件夹
     * @param string $source 源文件夹
     * @param string $dest   目标文件夹
     */
    function copydirs($source, $dest)
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }
        foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
            if ($item->isDir()) {
                $sontDir = $dest . DS . $iterator->getSubPathName();
                if (!is_dir($sontDir)) {
                    mkdir($sontDir, 0755, true);
                }
            } else {
                copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }
}

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string)
    {
        return mb_strtoupper(mb_substr($string, 0, 1)) . mb_strtolower(mb_substr($string, 1));
    }
}

if (!function_exists('addtion')) {

    /**
     * 附加关联字段数据
     * @param array $items  数据列表
     * @param mixed $fields 渲染的来源字段
     * @return array
     */
    function addtion($items, $fields)
    {
        if (!$items || !$fields) {
            return $items;
        }
        $fieldsArr = [];
        if (!is_array($fields)) {
            $arr = explode(',', $fields);
            foreach ($arr as $k => $v) {
                $fieldsArr[$v] = ['field' => $v];
            }
        } else {
            foreach ($fields as $k => $v) {
                if (is_array($v)) {
                    $v['field'] = $v['field'] ?? $k;
                } else {
                    $v = ['field' => $v];
                }
                $fieldsArr[$v['field']] = $v;
            }
        }
        foreach ($fieldsArr as $k => &$v) {
            $v = is_array($v) ? $v : ['field' => $v];
            $v['display'] = $v['display'] ?? str_replace(['_ids', '_id'], ['_names', '_name'], $v['field']);
            $v['primary'] = $v['primary'] ?? '';
            $v['column'] = $v['column'] ?? 'name';
            $v['model'] = $v['model'] ?? '';
            $v['table'] = $v['table'] ?? '';
            $v['name'] = $v['name'] ?? str_replace(['_ids', '_id'], '', $v['field']);
        }
        unset($v);
        $ids = [];
        $fields = array_keys($fieldsArr);
        foreach ($items as $k => $v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $ids[$n] = array_merge(isset($ids[$n]) && is_array($ids[$n]) ? $ids[$n] : [], explode(',', $v[$n]));
                }
            }
        }
        $result = [];
        foreach ($fieldsArr as $k => $v) {
            if ($v['model']) {
                $model = new $v['model'];
            } else {
                $model = $v['name'] ? \think\Db::name($v['name']) : \think\Db::table($v['table']);
            }
            $primary = $v['primary'] ? $v['primary'] : $model->getPk();
            $result[$v['field']] = isset($ids[$v['field']]) ? $model->where($primary, 'in', $ids[$v['field']])->column($v['column'], $primary) : [];
        }

        foreach ($items as $k => &$v) {
            foreach ($fields as $m => $n) {
                if (isset($v[$n])) {
                    $curr = array_flip(explode(',', $v[$n]));

                    $linedata = array_intersect_key($result[$n], $curr);
                    $v[$fieldsArr[$n]['display']] = $fieldsArr[$n]['column'] == '*' ? $linedata : implode(',', $linedata);
                }
            }
        }
        return $items;
    }
}

if (!function_exists('var_export_short')) {

    /**
     * 使用短标签打印或返回数组结构
     * @param mixed   $data
     * @param boolean $return 是否返回数据
     * @return string
     */
    function var_export_short($data, $return = true)
    {
        return var_export($data, $return);
        $replaced = [];
        $count = 0;

        //判断是否是对象
        if (is_resource($data) || is_object($data)) {
            return var_export($data, $return);
        }

        //判断是否有特殊的键名
        $specialKey = false;
        array_walk_recursive($data, function (&$value, &$key) use (&$specialKey) {
            if (is_string($key) && (stripos($key, "\n") !== false || stripos($key, "array (") !== false)) {
                $specialKey = true;
            }
        });
        if ($specialKey) {
            return var_export($data, $return);
        }
        array_walk_recursive($data, function (&$value, &$key) use (&$replaced, &$count, &$stringcheck) {
            if (is_object($value) || is_resource($value)) {
                $replaced[$count] = var_export($value, true);
                $value = "##<{$count}>##";
            } else {
                if (is_string($value) && (stripos($value, "\n") !== false || stripos($value, "array (") !== false)) {
                    $index = array_search($value, $replaced);
                    if ($index === false) {
                        $replaced[$count] = var_export($value, true);
                        $value = "##<{$count}>##";
                    } else {
                        $value = "##<{$index}>##";
                    }
                }
            }
            $count++;
        });

        $dump = var_export($data, true);

        $dump = preg_replace('#(?:\A|\n)([ ]*)array \(#i', '[', $dump); // Starts
        $dump = preg_replace('#\n([ ]*)\),#', "\n$1],", $dump); // Ends
        $dump = preg_replace('#=> \[\n\s+\],\n#', "=> [],\n", $dump); // Empties
        $dump = preg_replace('#\)$#', "]", $dump); //End

        if ($replaced) {
            $dump = preg_replace_callback("/'##<(\d+)>##'/", function ($matches) use ($replaced) {
                return $replaced[$matches[1]] ?? "''";
            }, $dump);
        }

        if ($return === true) {
            return $dump;
        } else {
            echo $dump;
        }
    }
}

if (!function_exists('letter_avatar')) {
    /**
     * 首字母头像
     * @param $text
     * @return string
     */
    function letter_avatar($text)
    {
        $total = unpack('L', hash('adler32', $text, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $bg = "rgb({$r},{$g},{$b})";
        $color = "#ffffff";
        $first = mb_strtoupper(mb_substr($text, 0, 1));
        $src = base64_encode('<svg xmlns="http://www.w3.org/2000/svg" version="1.1" height="100" width="100"><rect fill="' . $bg . '" x="0" y="0" width="100" height="100"></rect><text x="50" y="50" font-size="50" text-copy="fast" fill="' . $color . '" text-anchor="middle" text-rights="admin" dominant-baseline="central">' . $first . '</text></svg>');
        $value = 'data:image/svg+xml;base64,' . $src;
        return $value;
    }
}

if (!function_exists('hsv2rgb')) {
    function hsv2rgb($h, $s, $v)
    {
        $r = $g = $b = 0;

        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);

        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        return [
            floor($r * 255),
            floor($g * 255),
            floor($b * 255)
        ];
    }
}

if (!function_exists('check_nav_active')) {
    /**
     * 检测会员中心导航是否高亮
     */
    function check_nav_active($url, $classname = 'active')
    {
        $auth = \app\common\library\Auth::instance();
        $requestUrl = $auth->getRequestUri();
        $url = ltrim($url, '/');
        return $requestUrl === str_replace(".", "/", $url) ? $classname : '';
    }
}

if (!function_exists('check_cors_request')) {
    /**
     * 跨域检测
     */
    function check_cors_request()
    {
        if (isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN'] && config('fastadmin.cors_request_domain')) {
            $info = parse_url($_SERVER['HTTP_ORIGIN']);
            $domainArr = explode(',', config('fastadmin.cors_request_domain'));
            $domainArr[] = request()->host(true);
            if (in_array("*", $domainArr) || in_array($_SERVER['HTTP_ORIGIN'], $domainArr) || (isset($info['host']) && in_array($info['host'], $domainArr))) {
                header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
            } else {
                $response = Response::create('跨域检测无效', 'html', 403);
                throw new HttpResponseException($response);
            }

            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');

            if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                }
                if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
                $response = Response::create('', 'html');
                throw new HttpResponseException($response);
            }
        }
    }
}

if (!function_exists('xss_clean')) {
    /**
     * 清理XSS
     */
    function xss_clean($content, $is_image = false)
    {
        return \app\common\library\Security::instance()->xss_clean($content, $is_image);
    }
}

if (!function_exists('url_clean')) {
    /**
     * 清理URL
     */
    function url_clean($url)
    {
        if (!check_url_allowed($url)) {
            return '';
        }
        return xss_clean($url);
    }
}

if (!function_exists('check_ip_allowed')) {
    /**
     * 检测IP是否允许
     * @param string $ip IP地址
     */
    function check_ip_allowed($ip = null)
    {
        $ip = is_null($ip) ? request()->ip() : $ip;
        $forbiddenipArr = config('site.forbiddenip');
        $forbiddenipArr = !$forbiddenipArr ? [] : $forbiddenipArr;
        $forbiddenipArr = is_array($forbiddenipArr) ? $forbiddenipArr : array_filter(explode("\n", str_replace("\r\n", "\n", $forbiddenipArr)));
        if ($forbiddenipArr && \Symfony\Component\HttpFoundation\IpUtils::checkIp($ip, $forbiddenipArr)) {
            $response = Response::create('请求无权访问', 'html', 403);
            throw new HttpResponseException($response);
        }
    }
}

if (!function_exists('check_url_allowed')) {
    /**
     * 检测URL是否允许
     * @param string $url URL
     * @return bool
     */
    function check_url_allowed($url = '')
    {
        //允许的主机列表
        $allowedHostArr = [
            strtolower(request()->host())
        ];

        if (empty($url)) {
            return true;
        }

        //如果是站内相对链接则允许
        if (preg_match("/^[\/a-z][a-z0-9][a-z0-9\.\/]+((\?|#).*)?\$/i", $url) && substr($url, 0, 2) !== '//') {
            return true;
        }

        //如果是站外链接则需要判断HOST是否允许
        if (preg_match("/((http[s]?:\/\/)+(?>[a-z\-0-9]{2,}\.){1,}[a-z]{2,8})(?:\s|\/)/i", $url)) {
            $chkHost = parse_url(strtolower($url), PHP_URL_HOST);
            if ($chkHost && in_array($chkHost, $allowedHostArr)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('build_suffix_image')) {
    /**
     * 生成文件后缀图片
     * @param string $suffix 后缀
     * @param null   $background
     * @return string
     */
    function build_suffix_image($suffix, $background = null)
    {
        $suffix = mb_substr(strtoupper($suffix), 0, 4);
        $total = unpack('L', hash('adler32', $suffix, true))[1];
        $hue = $total % 360;
        list($r, $g, $b) = hsv2rgb($hue / 360, 0.3, 0.9);

        $background = $background ? $background : "rgb({$r},{$g},{$b})";

        $icon = <<<EOT
        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve">
            <path style="fill:#E2E5E7;" d="M128,0c-17.6,0-32,14.4-32,32v448c0,17.6,14.4,32,32,32h320c17.6,0,32-14.4,32-32V128L352,0H128z"/>
            <path style="fill:#B0B7BD;" d="M384,128h96L352,0v96C352,113.6,366.4,128,384,128z"/>
            <polygon style="fill:#CAD1D8;" points="480,224 384,128 480,128 "/>
            <path style="fill:{$background};" d="M416,416c0,8.8-7.2,16-16,16H48c-8.8,0-16-7.2-16-16V256c0-8.8,7.2-16,16-16h352c8.8,0,16,7.2,16,16 V416z"/>
            <path style="fill:#CAD1D8;" d="M400,432H96v16h304c8.8,0,16-7.2,16-16v-16C416,424.8,408.8,432,400,432z"/>
            <g><text><tspan x="220" y="380" font-size="124" font-family="Verdana, Helvetica, Arial, sans-serif" fill="white" text-anchor="middle">{$suffix}</tspan></text></g>
        </svg>
EOT;
        return $icon;
    }
}

//-----------------------------YUNJIAN---------------------------//
if (!function_exists('randstr')) {
    /**
     * 获得随机字符串
     * @param $len             需要的长度
     * @param $special        是否需要特殊符号
     * @return string       返回随机字符串
     */
    function randstr($len = 10, $special = false)
    {
        $chars = array(
            "a",
            "b",
            "c",
            "d",
            "e",
            "f",
            "g",
            "h",
            "i",
            "j",
            "k",
            "l",
            "m",
            "n",
            "o",
            "p",
            "q",
            "r",
            "s",
            "t",
            "u",
            "v",
            "w",
            "x",
            "y",
            "z",
            "A",
            "B",
            "C",
            "D",
            "E",
            "F",
            "G",
            "H",
            "I",
            "J",
            "K",
            "L",
            "M",
            "N",
            "O",
            "P",
            "Q",
            "R",
            "S",
            "T",
            "U",
            "V",
            "W",
            "X",
            "Y",
            "Z",
            "0",
            "1",
            "2",
            "3",
            "4",
            "5",
            "6",
            "7",
            "8",
            "9"
        );

        if ($special) {
            $chars = array_merge(
                $chars,
                array(
                    "!",
                    "@",
                    "#",
                    "$",
                    "?",
                    "|",
                    "{",
                    "/",
                    ":",
                    ";",
                    "%",
                    "^",
                    "&",
                    "*",
                    "(",
                    ")",
                    "-",
                    "_",
                    "[",
                    "]",
                    "}",
                    "<",
                    ">",
                    "~",
                    "+",
                    "=",
                    ",",
                    "."
                )
            );
        }

        $charsLen = count($chars) - 1;
        shuffle($chars); //打乱数组顺序
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)]; //随机取出一位
        }
        return $str;
    }
}

if (!function_exists('build_upload')) {
    /**
     * 获得随机字符串
     * @param $len             需要的长度
     * @param $special        是否需要特殊符号
     * @return string       返回随机字符串
     */
    function build_upload($name = '')
    {
        //封装返回值
        $success = [
            'result' => false,
            'msg' => '',
            'data' => ''
        ];

        //获取到文件
        $file = request()->file($name);

        if (!$file) {
            //没有图片
            $success['result'] = false;
            $success['msg'] = '无图片上传';
            return $success;
        }

        //直接将文件，移动到 public/uploads/
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');

        if ($info) {
            $success['result'] = true;
            $success['msg'] = '上传成功';
            $success['data'] = '/uploads/' . $info->getSaveName();
        } else {
            //移动失败
            $success['result'] = false;
            $success['msg'] = $file->getError();
        }

        return $success;
    }

}

if (!function_exists('build_code')) {
    /**
     * 生成唯一订单号
     * @param String $prefix 指定的订单前缀
     * @return String  返回字符串
     */

    function build_code($prefix = "")
    {
        @date_default_timezone_set("PRC");
        $order_id_main = date('YmdHis') . rand(10000, 99999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int) (substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $osn = $prefix . $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT); //生成唯一订单号
        return $osn;
    }
}

if (!function_exists('GetClientIP')) {
    function GetClientIP()
    {
        // 调用接口 返回json
        $json = file_get_contents("http://ip-api.com/json/");

        // 把json 变成 php数组
        $result = json_decode($json, true);

        // 组合数据
        $data = [
            'lon' => $result['lon'],
            'lat' => $result['lat']
        ];

        return $data;
    }
}

if (!function_exists('build_code')) {
    /**
     * 生成唯一订单号
     * @param String $prefix 指定的订单前缀
     * @return String  返回字符串
     */

    function build_code($prefix = "")
    {
        @date_default_timezone_set("PRC");
        $order_id_main = date('YmdHis') . rand(10000, 99999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int) (substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $osn = $prefix . $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT); //生成唯一订单号
        return $osn;
    }
}

if(!function_exists('httpRequest'))
{
    function httpRequest($url, $data = null)
    {
        if (function_exists('curl_init')) {
            $curl = curl_init();
            // 设置请求地址
            curl_setopt($curl, CURLOPT_URL, $url);

            // 设置http某些配置
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);

            // 判断传进来的参数是否不为空
            if (!empty($data)) {
                // 设置该请求为POST
                curl_setopt($curl, CURLOPT_POST, 1);
                // 把参数带入请求
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($curl);
            curl_close($curl);
            return $output;
        } else {
            return false;
        }
    }
}

if (!function_exists('query_express')) {
    /**
     * 查询物流信息
     * @param String $code 物流单号
     * @return Array  返回物流信息
     */

    function query_express($code = "")
    {
        if (empty($code)) {
            return false;
        }

        // 接口购买地址：https://market.aliyun.com/products/57126001/cmapi023201.html

        // 请求地址
        $url = "https://wdexpress.market.alicloudapi.com/gxali?n=$code";

        // 请求头信息
        $headers = [];
        $appcode = "672dfaf3140047259b93779d7d4ada5a";
        array_push($headers, "Authorization:APPCODE " . $appcode);


        // 发起请求
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET"); // 设置GET请求方法
        curl_setopt($curl, CURLOPT_URL, $url); // 设置请求地址
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers); // 设置请求头
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 直接拿到返回值
        curl_setopt($curl, CURLOPT_HEADER, true);
        // 取消https的ssl效验
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        // 发起请求
        $result = curl_exec($curl);

        // 返回请求的状态码
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($httpCode != 200) {
            return false;
        }

        // 转换结果
        list($header, $body) = explode("\r\n\r\n", $result, 2);

        // $body = '{"LogisticCode":"773250575667871","ShipperCode":"STO","Traces":[{"AcceptStation":"[东莞市]【广东东莞虎门四服务点】(0769-33555666)的黄江-黄文(1809823768)已揽收","AcceptTime":"2023-10-25 17:10:55"},{"AcceptStation":"[东莞市]快件已到达【广东东莞虎门集散中心】","AcceptTime":"2023-10-26 02:16:04"},{"AcceptStation":"[东莞市]快件离开【广东东莞虎门集散中心】已发往【广东深圳龙华集散中心】","AcceptTime":"2023-10-26 02:22:59"},{"AcceptStation":"[东莞市]快件离开【广东东莞虎门集散中心】已发往【广东广州转运中心】","AcceptTime":"2023-10-26 02:27:41"},{"AcceptStation":"[广州市]快件已到达【广东广州转运中心】","AcceptTime":"2023-10-26 05:23:14"},{"AcceptStation":"[广州市]快件已到达【广东广州转运中心】","AcceptTime":"2023-10-26 06:21:05"},{"AcceptStation":"[广州市]快件离开【广东广州转运中心】已发往【广东广州南洲公司】","AcceptTime":"2023-10-26 06:27:14"},{"AcceptStation":"[广州市]快件已到达【广东广州南洲公司】咨询电话：020-88811135","AcceptTime":"2023-10-26 14:53:44"},{"AcceptStation":"[广州市]【广东广州南洲公司】的申通小哥(张正大\/13247652215)正在为您派送(可放心接听95089申通专属派送号码)，投诉电话:02088811135","AcceptTime":"2023-10-26 15:03:05"},{"AcceptStation":"快件已被华俊后街韵达快递超市代收，请及时取件。如有取件码问题或找不到包裹等问题，请联系：13247652215，如您未收到此快递，请拨打投诉电话：02088811135! ","AcceptTime":"2023-10-26 16:43:37"},{"AcceptStation":"[驿站]包裹已签收！签收人凭取货码签收，如有问题请联系：韵达超市13247652215，投诉电话：02088811135。起早贪黑不停忙，如有不妥您见谅，好评激励我向上，求个五星暖心房。","AcceptTime":"2023-10-26 19:10:03"}],"State":"3","Success":true,"Courier":"","CourierPhone":"13247652215","updateTime":"2023-10-26 19:10:03","takeTime":"1天1小时59分","Name":"申通快递","Site":"www.sto.cn","Phone":"95543","Logo":"https:\/\/img3.fegine.com\/express\/sto.jpg","Reason":"查询成功"}';

        // 将json字符串转换为数组结构
        $body = json_decode($body, true);

        // 物流名称
        $name = isset($body['Name']) ? $body['Name'] : '';

        // 快递员的电话号码
        $phone = isset($body['CourierPhone']) ? $body['CourierPhone'] : '';

        // 物流信息
        $list = (isset($body['Traces']) && !empty($body['Traces'])) ? $body['Traces'] : [];

        $data = [
            'name' => $name,
            'phone' => $phone,
            'list' => $list
        ];

        return $data;
    }
}

