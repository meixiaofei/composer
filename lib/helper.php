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
 * 对查询结果集进行排序
 * @access public
 * @param array  $list 查询结果
 * @param string $field 排序的字段名
 * @param string $sortBy 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
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
 * @param        $url 请求URL
 * @param array  $params 请求参数
 * @param string $method 请求方法GET /POST
 * @param array  $header
 * @param bool   $postFile
 * @return mixed $data 响应数据
 */
function http($url, $params = [], $method = 'GET', $header = [], $postFile = false)
{
    $opts = [
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => $header,
    ];
    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET' :
            $opts[CURLOPT_URL] = $params ? $url . '?' . http_build_query($params) : $url;
            break;
        case 'POST' :
            // 判断是否传输文件
            $params                   = $postFile ? $params : http_build_query($params);
            $opts[CURLOPT_URL]        = $url;
            $opts[CURLOPT_POST]       = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default :
            exit ('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data  = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error)
        exit ('请求发生错误：' . $error);
    
    return $data;
}

/**
 * @param $expTitle
 * @param $expCellName
 * @param $expTableData
 * @throws Exception
 */
function export_excel($expTitle, $expCellName, $expTableData)
{
    $xlsTitle = $expTitle;
    $fileName = $xlsTitle . '-' . date('YmdHis');
    $cellNum  = count($expCellName);
    $dataNum  = count($expTableData);
    
    $objPHPExcel = new PHPExcel();
    $cellName    = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ'];
    
    $objPHPExcel->getActiveSheet(0)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');
    $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle . '  Export time:' . date('Y-m-d H:i:s'));
    for ($i = 0; $i < $cellNum; $i++) {
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
        // 设置单元格之间的距离
        $objPHPExcel->getActiveSheet()->getColumnDimension($cellName[$i])->setWidth(18);
    }
    for ($i = 0; $i < $dataNum; $i++) {
        for ($j = 0; $j < $cellNum; $j++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j] . ($i + 3), $expTableData[$i][$expCellName[$j][0]]);
        }
    }
    ob_end_clean();
    header('pragma:public');
    header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
    header("Content-Disposition:attachment;filename=$fileName.xls");
    
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}

/**
 * @param $filename
 * @return array
 * @throws Exception
 */
function read_excel($filename)
{
    if (pathinfo($filename, PATHINFO_EXTENSION) == 'xlsx') {
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
    } else {
        $objReader = PHPExcel_IOFactory::createReader('Excel5');
    }
    
    $objReader->setReadDataOnly(true);
    $objPHPExcel = $objReader->load($filename);
    
    $data = [];
    for ($i = 0, $sheetLength = $objPHPExcel->getSheetCount(); $i < $sheetLength; $i++) {
        $objWorksheet       = $objPHPExcel->getSheet($i);
        $highestRow         = $objWorksheet->getHighestRow();
        $highestColumn      = $objWorksheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
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
