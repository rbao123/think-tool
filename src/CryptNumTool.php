<?php

namespace bao\tool;

/**
 * Class CryptNumTool 数字加密
 * @package app\common\tool
 */
class CryptNumTool
{
    private $strbase = "Flpvf70CsakVjqgeWUPXQxSyJizmNH6B1u3b8cAEKwTd54nRtZOMDhoG2YLrI";
    private $key, $length, $codelen, $codenums, $codeext;

    public function __construct($length = 12, $key = 5622.1236523689){
        $this->key = $key;
        $this->length = $length;
        $this->codelen = substr($this->strbase,0,$this->length);
        $this->codenums = substr($this->strbase,$this->length,10);
        $this->codeext = substr($this->strbase,$this->length + 10);
    }

    // 编码
    public function encode($nums){
        $rtn = "";
        $numslen = strlen($nums);
        $begin = substr($this->codelen,$numslen - 1,1);
        $extlen = $this->length - $numslen - 1;
        $temp = str_replace('.', '', $nums / $this->key);
        $temp = substr($temp,-$extlen);
        $arrextTemp = str_split($this->codeext);
        $arrext = str_split($temp);
        foreach ($arrext as $v) {
            $rtn .= $arrextTemp[$v];
        }
        $arrnumsTemp = str_split($this->codenums);
        $arrnums = str_split($nums);
        foreach ($arrnums as $v) {
            $rtn .= $arrnumsTemp[$v];
        }
        return $begin.$rtn;
    }

    //解码
    public function decode($code){
        $begin = substr($code,0,1);
        $rtn = '';
        $len = strpos($this->codelen,$begin);
        if($len !== false){
            $len++;
            $arrnums = str_split(substr($code,-$len));
            foreach ($arrnums as $v) {
                $rtn .= strpos($this->codenums,$v);
            }
        }
        return $rtn;
    }
}