<?php
/**
 * common函数
 *
 * @author camera360_server@camera360.com
 * @copyright Chengdu pinguo Technology Co.,Ltd.
 */

$____GLOBAL_DUMP = '';

/**
 * 输出到控制台
 *
 * @param string $messages 输出的到控制台数据
 */
function writeln($messages)
{
    $msgStr  = (string)$messages;
    $msgStr  = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $msgStr);
    $logFile = getInstance()->config->get('server.set.log_file', '');
    if ($logFile) {
        file_put_contents($logFile, $msgStr, FILE_APPEND);
    } else {
        echo $msgStr;
    }
}

/**
 * 获取实例
 * @return \PG\MSF\MSFServer|\PG\MSF\MSFCli
 */
function &getInstance()
{
    return \PG\MSF\Server::getInstance();
}

/**
 * 清理所有的定时器（请谨慎使用）
 */
function clearTimes()
{
    $timers = getInstance()->sysTimers;
    if (!empty($timers)) {
        foreach ($timers as $timerId) {
            swoole_timer_clear($timerId);
        }
    }
    swoole_event_exit();
}

/**
 * 内部打印变量
 *
 * @param string $output 打印的结果
 * @param mixed $var 待打印的变量
 * @param int $level 打印的层级，最大4层
 * @param bool $format 是否格式化返回，默认为true
 * @param bool $truncated 大字符串是否截断，默认为true
 */
function dumpInternal(&$output, $var, $level, $format = true, $truncated = true)
{
    switch (gettype($var)) {
        case 'boolean':
            $output .= $var ? 'true' : 'false';
            break;
        case 'integer':
            $output .= "$var";
            break;
        case 'double':
            $output .= "$var";
            break;
        case 'string':
            if ($truncated && defined('DUMP_TRUNCATED') && strlen($var) > 512) {
                $output .= "'*<truncated>*'";
            } else {
                $output .= "'" . addslashes($var) . "'";
            }
            break;
        case 'resource':
            $output .= '{resource}';
            break;
        case 'NULL':
            $output .= 'null';
            break;
        case 'unknown type':
            $output .= '{unknown}';
            break;
        case 'array':
            if (4 <= $level) {
                $output .= '[...]';
            } elseif (empty($var)) {
                $output .= '[]';
            } else {
                if ($format) {
                    $spaces = str_repeat(' ', $level * 4);
                } else {
                    $spaces = '';
                }

                $output .= '[';
                foreach ($var as $key => $val) {
                    if (!isset($var[$key])) {
                        continue;
                    }

                    if ($format) {
                        $output .= "\n" . $spaces . '    ';
                    }
                    dumpInternal($output, $key, 0, $format);
                    $output .= ' => ';
                    dumpInternal($output, $var[$key], $level + 1, $format);
                    if (!$format) {
                        $output .= ', ';
                    }
                }

                if ($format) {
                    $output .= "\n" . $spaces . ']';
                } else {
                    $output .= "], ";
                }
            }
            break;
        case 'object':
            if ($var instanceof \swoole_http_response || $var instanceof \swoole_http_request) {
                $output .= get_class($var) . '(...)';
                break;
            }

            if ($var instanceof \Throwable) {
                $truncated  = false;
                $dumpValues = [
                    'message' => $var->getMessage(),
                    'code'    => $var->getCode(),
                    'line'    => $var->getLine(),
                    'file'    => $var->getFile(),
                    'trace'   => $var->getTraceAsString(),
                ];
            } else {
                $dumpValues = (array)$var;
                $truncated  = true;
            }
            if (method_exists($var, '__sleep')) {
                $sleepProperties = $var->__sleep();
                if (empty($sleepProperties)) {
                    $sleepProperties = array_keys($dumpValues);
                }
            } else {
                $sleepProperties = array_keys($dumpValues);
            }

            if (method_exists($var, '__unsleep')) {
                $unsleepProperties = $var->__unsleep();
            } else {
                $unsleepProperties = [];
            }
            $sleepProperties = array_diff($sleepProperties, $unsleepProperties);

            if (4 <= $level) {
                $output .= get_class($var) . '(...)';
            } else {
                $spaces = str_repeat(' ', $level * 4);
                $className = get_class($var);
                if ($format) {
                    $output .= "$className\n" . $spaces . '(';
                } else {
                    $output .= "$className(";
                }

                $i = 0;
                foreach ($dumpValues as $key => $value) {
                    if (!in_array(trim($key, "* \0"), $sleepProperties)) {
                        continue;
                    }

                    if ($i >= 100) {
                        if ($format) {
                            $output .= "\n" . $spaces . "    [...] => ...";
                        } else {
                            $output .= "... => ...";
                        }
                        break;
                    }
                    $i++;
                    $key = str_replace('*', '', $key);
                    $key = strtr(trim($key), "\0", ':');
                    $keyDisplay = strtr(trim($key), "\0", ':');
                    if ($format) {
                        $output .= "\n" . $spaces . "    [$keyDisplay] => ";
                    } else {
                        $output .= "$keyDisplay => ";
                    }
                    dumpInternal($output, $value, $level + 1, $format, $truncated);
                    if (!$format) {
                        $output .= ', ';
                    }
                }

                if ($format) {
                    $output .= "\n" . $spaces . ')';
                } else {
                    $output .= '), ';
                }
            }
            break;
    }

    if (!$format) {
        $output = str_replace([', ,', ',  ', ', )', ', ]'], [', ', ', ', ')', ']'], $output);
    }
}

/**
 * 打印变量
 *
 * @param mixed $var 打印的变量
 * @param bool $format 是否格式化
 * @param bool $return 是否直接返回字符串，而直接打印
 * @return mixed
 */
function dump($var, $format = true, $return = false)
{
    global $____GLOBAL_DUMP;
    dumpInternal($____GLOBAL_DUMP, $var, 0, $format);
    if (!$return) {
        echo $____GLOBAL_DUMP, "\n";
        $____GLOBAL_DUMP = '';
    } else {
        $dump            = $____GLOBAL_DUMP;
        $____GLOBAL_DUMP = '';
        return $dump;
    }
}
