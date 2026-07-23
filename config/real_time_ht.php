<?php
// Tổng hợp các processing_time từ it_checks.json, dựng các mốc thời gian dựa trên thực tế
return [
    [
        "key" => "1_HOUR",
        "name" => "1 giờ",
        "min_seconds" => 0,
        "max_seconds" => 3600
    ],
    [
        "key" => "2_HOURS",
        "name" => "2 giờ",
        "min_seconds" => 0,
        "max_seconds" => 7200
    ],
    [
        "key" => "4_HOURS",
        "name" => "Dưới 4 tiếng",
        "min_seconds" => 0,
        "max_seconds" => 14400
    ],
    [
        "key" => "8_HOURS",
        "name" => "Dưới 8 tiếng",
        "min_seconds" => 0,
        "max_seconds" => 28800
    ],
    [
        "key" => "8_10_HOURS",
        "name" => "Từ 8 tiếng - 10 tiếng",
        "min_seconds" => 28800,
        "max_seconds" => 36000
    ],
    [
        "key" => "12_HOURS",
        "name" => "Dưới 12 tiếng",
        "min_seconds" => 0,
        "max_seconds" => 43200
    ],
    [
        "key" => "1_DAY",
        "name" => "1 ngày",
        "min_seconds" => 0,
        "max_seconds" => 86400
    ],
    [
        "key" => "2_DAYS",
        "name" => "2 ngày",
        "min_seconds" => 86400,
        "max_seconds" => 172800
    ],
    [
        "key" => "3_DAYS",
        "name" => "3 ngày",
        "min_seconds" => 172800,
        "max_seconds" => 259200
    ],
    [
        "key" => "3_5_DAYS",
        "name" => "3-5 ngày",
        "min_seconds" => 259200,
        "max_seconds" => 432000
    ],
    [
        "key" => "7_15_DAYS",
        "name" => "7-15 ngày",
        "min_seconds" => 604800,
        "max_seconds" => 1296000
    ],
    [
        "key" => "BEFORE_20_DAYS",
        "name" => "Trước Open 20 ngày",
        "min_seconds" => null,
        "max_seconds" => null
    ],
    [
        "key" => "BEFORE_4_DAYS",
        "name" => "Trước Open 4 ngày",
        "min_seconds" => null,
        "max_seconds" => null
    ],
    [
        "key" => "BEFORE_2_DAYS",
        "name" => "Trước Open 2 ngày",
        "min_seconds" => null,
        "max_seconds" => null
    ],
    [
        "key" => "BEFORE_1_DAYS",
        "name" => "Trước Open 1 ngày",
        "min_seconds" => null,
        "max_seconds" => null
    ],
    [
        "key" => "AFTER_CLOSE_DECISION",
        "name" => "Sau quyết định đóng cửa",
        "min_seconds" => null,
        "max_seconds" => null
    ],
    [
        "key" => "EVERY_3_MONTHS",
        "name" => "3 tháng/lần/cửa hàng",
        "min_seconds" => null,
        "max_seconds" => null
    ],
    [
        "key" => "OTHER",
        "name" => "Theo lịch / Khác",
        "min_seconds" => null,
        "max_seconds" => null
    ],
];