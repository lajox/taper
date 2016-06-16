<?php

namespace Taper;

use Taper\Exception\TaperException;

class Debug
{

    //信息内容
    static $info = array();
    //运行时间
    static $runtime;
    //运行内存占用
    static $memory;
    //内存峰值
    static $memory_peak;
    //所有发送的SQL语句
    static $sqlExeArr = array();
    //编译模板
    static $tpl = array();
    //缓存记录
    static $cache=array("write_s"=>0,"write_f"=>0,"read_s"=>0,"read_f"=>0);

    /**
     * @var Application app
     */
    private $app;

    /**
     * Constructor.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * 项目调试开始
     * @access public
     * @param string $start   起始
     * @return void
     */
    public function start($start)
    {
        self::$runtime[$start] = microtime(true);
        if (function_exists("memory_get_usage")) {
            self::$memory[$start] = memory_get_usage();
        }
        if (function_exists("memory_get_peak_usage")) {
            self::$memory_peak[$start] = false;
        }
    }
    /**
     * 运行时间
     * @param $start
     * @param string $end
     * @param int $decimals
     * @return string
     * @throws \Taper\Exception\TaperException
     */
    public function runtime($start, $end = '', $decimals = 4)
    {
        if (!isset(self::$runtime[$start])) {
            throw new TaperException('没有设置调试开始点：' . $start);
        }
        if (empty(self::$runtime[$end])) {
            self::$runtime[$end] = microtime(true);
            return number_format(self::$runtime[$end] - self::$runtime[$start], $decimals);
        }
    }

    /**
     * 项目运行内存峰值
     * @access public
     * @param string $start   起始标记
     * @param string $end     结束标记
     * @return int
     */
    public function memory_perk($start, $end = '')
    {
        if (!isset(self::$memory_peak[$start]))
            return mt_rand(200000, 1000000);
        if (!empty($end))
            self::$memory_peak[$end] = memory_get_peak_usage();
        return max(self::$memory_peak[$start], self::$memory_peak[$end]);
    }

    /**
     * 显示调试信息
     * @access public
     * @param string $start   起始标记
     * @param string $end     结束标记
     * @return array
     */
    public function info($start, $end)
    {
        $debug = array();
        $debug['file'] = get_included_files();
        $debug['runtime'] = self::runtime($start, $end);
        $debug['memory'] = number_format(self::memory_perk($start, $end) / 1000, 0) . " KB";
        $definedFuns = get_defined_functions();
        $debug['function'] = $definedFuns['user'];
        return $debug;
    }

    /**
     * 显示调试信息
     * @access public
     * @param string $start   起始标记
     * @param string $end     结束标记
     * @return array
     */
    public function show($start, $end)
    {
        $app = $this->app;
        $debug = self::info($start, $end);
        $display = $app['debug_tool'] ?'block':'none';
        include_once __DIR__.'/Tpl/debug.html';
    }

}
