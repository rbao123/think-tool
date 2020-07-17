<?php
/**
 * Created by PhpStorm.
 * User: zhao
 * Date: 2018/10/29
 * Time: 16:58
 */

namespace bao\tool;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use think\File;
use PhpOffice\PhpSpreadsheet\Reader\Xls as R_Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as R_Xlsx;

class ExcelTool
{
    /**
     * 创建(导出)Excel数据表格
     * @param array $list 要导出的数组格式的数据
     * @param string $filename 导出的Excel表格数据表的文件名
     * @param array $indexKey $list数组中与Excel表格表头$title中每个项目对应的字段的名字(key值)当$list全部要导出时可为空
     * @param array $title 标题
     * @param bool $xlsx 是否生成Excel2007(.xlsx)以上兼容的数据表
     * @throws
     * @return mixed
     */
    public static function exportExcel(array $list, $filename, array $indexKey, array $title, $xlsx = false)
    {
        /**
         * 比如: $indexKey与$list数组对应关系如下:
         *     $indexKey = array('id','username','sex','age');
         *     $list = array(array('id'=>1,'username'=>'YQJ','sex'=>'男','age'=>24));
         */
        if (empty($filename)) $filename = time();
        if (!is_array($indexKey)) return false;

        // $header_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        //初始化PHPExcel()
        $objPHPExcel = new Spreadsheet();

        //设置保存版本格式
        if ($xlsx) {
            $objWriter = new Xlsx($objPHPExcel);
            $filename = $filename . '.xlsx';
        } else {
            $objWriter = new Xls($objPHPExcel);
            $filename = $filename . '.xls';
        }
        //接下来就是写数据到表格里面去
        //表头
        //设置单元格内容
        $objActSheet = $objPHPExcel->getActiveSheet();
        $objActSheet->setTitle('工作表格1');
        foreach ($title as $key => $value) {
            $objActSheet->setCellValueByColumnAndRow($key + 1, 1, $value);
        }
        $startRow = 2;
        foreach ($list as $row) {
            $column = 1;
            if (!$indexKey) {
                foreach ($row as $key => $value) {
                    //这里是设置单元格的内容
                    $objActSheet->setCellValueByColumnAndRow($column, $startRow, $value);
                    $column++;
                }
            } else {
                foreach ($indexKey as $key => $value) {
                    //这里是设置单元格的内容
                    $objActSheet->setCellValueByColumnAndRow($column, $startRow, $row[$value]);
                    $column++;
                }
            }
            $startRow++;
        }
        // 下载这个表格，在浏览器输出
        header('Access-Control-Allow-Origin:*');
        header('Access-Control-Allow-Credentials:true');
        header('Access-Control-Expose-Headers:Content-Disposition');
        header('Access-Control-Allow-Methods:GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers:Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename=' . $filename);
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');

//        $fp = fopen('php://output', 'a');//打开output流
//        mb_convert_variables('GBK', 'UTF-8', $columns);
//        fputcsv($fp, $columns);//将数据格式化为xlsx格式并写入到output流中
//
//        $dataNum = count($list);
//        $perSize = 1000;//每次导出的条数
//        $pages = ceil($dataNum / $perSize);
//
//        for ($i = 1; $i <= $pages; $i++) {
//            foreach ($list as $item) {
//                mb_convert_variables('GBK', 'UTF-8', $item);
//                fputcsv($fp, $item);
//            }
//            //刷新输出缓冲到浏览器
//            ob_flush();
//            flush();//必须同时使用 ob_flush() 和flush() 函数来刷新输出缓冲。
//        }
//        fclose($fp);
        exit();

    }

    /**
     * 导入excel
     * @param object $file excel文件
     * @return array
     * @throws
     */
    public static function Import($file)
    {
        if ($file instanceof File) {
            $path = self::createLocalFile($file, 'uploads' . DIRECTORY_SEPARATOR . 'excel');
            if (PHP_OS == 'WINNT') {
                $path = str_replace('\\', '/', $path);
            }
        }
        if (pathinfo($path, PATHINFO_EXTENSION) == 'xlsx') {
            $reader = new R_Xlsx();
        } else {
            $reader = new R_Xls();
        }
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $data = $spreadsheet->getActiveSheet()->toArray();
        return $data;
    }

    /**
     * 创建本地文件
     * @param \think\File $file
     * @param $path
     * @return mixed 返回文件路径
     * @throws
     */
    public static function createLocalFile($file, $path)
    {
        $info = $file->move($path);
        if ($info) {
            // 成功上传后 获取上传信息
            return $path . DIRECTORY_SEPARATOR . $info->getSaveName();
        } else {
            // 上传失败获取错误信息
           abort($file->getError());
        }
    }

    /**
     * 导出excel(csv)
     * @param array $data 导出数据
     * @param array $headlist 第一行,列名
     * @param string $fileName 输出Excel文件名
     */
    public static function csv_export($data, $fileName, $headlist)
    {
        //header设置
        header("Cache-Control: max-age=0");
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment;filename="' . $fileName . '.csv"');

        //输出指定大小数据到是保存在内存中，如果它超过设置值，则自动写入临时文件
        $csv = fopen('php://temp/maxmemory:' . (100 * 1024 * 1024), 'r+');

        //添加BOM来修复UTF-8乱码
        fwrite($csv, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($csv, $headlist);
        $num=0;
        $limit=10000;
        foreach ($data as $v) {
            $num++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            foreach ($v as $k => $temp) {
                $temp = str_replace(',', '，', $temp);
                if (is_numeric($temp))
                    $v[$k] = '`' . $temp;
            }
            fputcsv($csv, $v);
        }
        rewind($csv);
        exit(stream_get_contents($csv));
    }
}