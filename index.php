<?php

require 'vendor/autoload.php';

$excelTitle = 'Test export';
$cellName   = [
    ['id', 'Id'],
    ['name', '姓名'],
    ['gender', '性别'],
    ['province', '省'],
    ['city', '市'],
    ['area', '区'],
    // ...
];
$cellData   = [
    ['id' => '1', 'name' => '张三', 'gender' => '男', 'province' => '湖北省', 'city' => '武汉市', 'area' => '武昌区'],
    ['id' => '2', 'name' => '李四', 'gender' => '女', 'province' => '湖南省', 'city' => '长沙市', 'area' => '岳麓区'],
    ['id' => '3', 'name' => '王五', 'gender' => '男', 'province' => '广东省', 'city' => '汕头市', 'area' => '龙湖区'],
];

export_excel($excelTitle, $cellName, $cellData);
$data = read_excel('/Users/fei/Downloads/Test export-20190430173626.xlsx');
fei_print($data);
