<?php

declare(strict_types=1);

namespace dove\tool;

use dove\Config;

// 对用户的操作

class User
{
    /**
     * 会员是否到期(使用日期-时间判断)
     * @return array
     */
    public static function vip_status($dueTime = '1970-01-01 00:00:00', $regTime = '1970-01-01 00:00:00')
    {
        if (time() > strtotime($dueTime)) {
            if (strtotime($dueTime) == strtotime($regTime)) {
                return [false, $regTime];
            } else {
                return [false, $dueTime, $regTime];
            }
        } else {
            return [true, $dueTime, $regTime];
        }
    }
}