<?php
/**
 * @param array $arr
 * @param bool  $die
 */
function fei_print($arr = [], $die = true)
{
    echo '<pre>';
    print_r($arr);
    $die && die;
}

/**
 * @param $array
 * @param $position
 * @param $insertArray
 *
 * @return array
 */
function array_insert(&$array, $position, $insertArray)
{
    $firstArray = array_splice($array, 0, $position);
    $array      = array_merge($firstArray, $insertArray, $array);

    return $array;
}

/**
 * 对查询结果集进行排序
 * @access public
 *
 * @param array  $list   查询结果
 * @param string $field  排序的字段名
 * @param string $sortBy 排序类型
 *                       asc正向排序 desc逆向排序 nat自然排序
 *
 * @return array
 */
function list_sort_by($list, $field, $sortBy = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = [];
        foreach ($list as $i => $data)
            $refer[$i] = &$data[$field];
        switch ($sortBy) {
            case 'asc': // 正向排序
                asort($refer);
                break;
            case 'desc':// 逆向排序
                arsort($refer);
                break;
            case 'nat': // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val)
            $resultSet[] = &$list[$key];

        return $resultSet;
    }

    return false;
}

/**
 * 发送HTTP请求方法，目前只支持CURL发送请求
 *
 * @param        $url    请求URL
 * @param array  $params 请求参数
 * @param string $method 请求方法GET /POST
 * @param array  $header
 *
 * @return mixed $data 响应数据
 */
function http($url, $params = [], $method = 'get', $header = [])
{
    $opts = [
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header,
        CURLOPT_HEADER         => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
    ];
    switch ($method) {
        case 'get' :
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'post' :
            // 判断是否传输文件
            $params                   = is_array($params) ? http_build_query($params) : $params;
            $opts[CURLOPT_URL]        = $url;
            $opts[CURLOPT_POST]       = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default :
            exit('不支持的请求方式！');
    }
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        exit('请求发生错误：' . $error);
    }

    return $data;
}

/**
 * @param $expTitle
 * @param $expCellName
 * @param $expTableData
 *
 * @throws Exception
 */
function export_excel($expTitle, $expCellName, $expTableData)
{
    $xlsTitle = $expTitle;
    $fileName = $xlsTitle . '-' . date('YmdHis');
    $cellNum  = count($expCellName);
    $dataNum  = count($expTableData);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $spreadsheet->getActiveSheet()->mergeCells('A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cellNum - 1) . '1');
    $spreadsheet->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  Export time: ' . date('Y-m-d H:i:s'));
    for ($i = 0; $i < $cellNum; $i++) {
        $spreadsheet->setActiveSheetIndex(0)->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i) . '2', $expCellName[$i][1]);
        // set cell width
        $spreadsheet->getActiveSheet()->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i))->setWidth(18);
    }
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            if (false !== strpos($expCellName[$j][0], '.')) {
                list($key1, $key2) = explode('.', $expCellName[$j][0]);
                $spreadsheet->getActiveSheet()->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j) . ($i + 3), isset($expTableData[$i][$key1][$key2]) ? $expTableData[$i][$key1][$key2] : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            } else {
                $spreadsheet->getActiveSheet()->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($j) . ($i + 3), isset($expTableData[$i][$expCellName[$j][0]]) ? $expTableData[$i][$expCellName[$j][0]] : '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
    }
    ob_end_clean();
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xlsx"');
    header("Content-Disposition:attachment;filename=$fileName.xlsx");

    $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $objWriter->save('php://output');
    exit;
}

/**
 * @param $filename
 *
 * @return array
 * @throws Exception
 */
function read_excel($filename)
{
    $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst(pathinfo($filename, PATHINFO_EXTENSION)));
    $objReader->setReadDataOnly(true);
    $spreadsheet = $objReader->load($filename);

    $data = [];
    for ($i = 0, $sheetLength = $spreadsheet->getSheetCount(); $i < $sheetLength; $i++) {
        $objWorksheet       = $spreadsheet->getSheet($i);
        $highestRow         = $objWorksheet->getHighestRow();
        $highestColumn      = $objWorksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        $excelData          = [];
        // the zero index row is alpha A-Z...AA-ZZ
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($col = 0; $col < $highestColumnIndex; $col++) {
                $value                 = (string)$objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
                $excelData[$row - 1][] = $value;
            }
        }
        $data[] = $excelData;
    }

    return $data;
}
