<?php


namespace bao\tool;


use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\LabelAlignment;
use Endroid\QrCode\QrCode;
use Exception;

class QRTool
{
    protected $_qr;
    protected $_encoding = 'UTF-8';
    protected $_size = 200;
    protected $_logo = true;
    protected $_logo_url = '';
    protected $_logo_size = 40;
    protected $_title = false;
    protected $_title_content = '';
    protected $_generate = 'display'; // display-直接显示 writefile-写入文件
    const MARGIN = 10;
    const WRITE_NAME = 'png';
    const FOREGROUND_COLOR = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
    const BACKGROUND_COLOR = ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0];

    public function __construct($config) {
        isset($config['generate']) && $this->_generate = $config['generate'];
        isset($config['encoding']) && $this->_encoding = $config['encoding'];
        isset($config['size']) && $this->_size = $config['size'];
        isset($config['display']) && $this->_size = $config['size'];
        isset($config['logo']) && $this->_logo = $config['logo'];
        isset($config['logo_url']) && $this->_logo_url = $config['logo_url'];
        isset($config['logo_size']) && $this->_logo_size = $config['logo_size'];
        isset($config['title']) && $this->_title = $config['title'];
        isset($config['title_content']) && $this->_title_content = $config['title_content'];
    }

    /**
     * 生成二维码
     * @param string $content 需要写入的内容
     * @return array | page input
     */
    public function create($content) {
        $this->_qr = new QrCode($content);
        $this->_qr->setSize($this->_size);
        $this->_qr->setWriterByName(self::WRITE_NAME);
        $this->_qr->setMargin(self::MARGIN);
        $this->_qr->setEncoding($this->_encoding);
        $this->_qr->setErrorCorrectionLevel(new ErrorCorrectionLevel(ErrorCorrectionLevel::HIGH));
        $this->_qr->setForegroundColor(self::FOREGROUND_COLOR);
        $this->_qr->setBackgroundColor(self::BACKGROUND_COLOR);
        if ($this->_title) {
            $this->_qr->setLabel($this->_title_content, 16, '字体地址', LabelAlignment::CENTER);
        }
        if ($this->_logo) {
            $this->_qr->setLogoPath($this->_logo_url);
            $this->_qr->setLogoWidth($this->_logo_size);
            $this->_qr->setRoundBlockSize(true);
        }
        $this->_qr->setValidateResult(false);

        if ($this->_generate == 'display') {
            // 前端调用 例：<img src="http://localhost/qr.php?url=base64_url_string">
//            header('Content-Type: ' . $this->_qr->getContentType());
            return $this->_qr->writeString();
        } else if ($this->_generate == 'writefile') {
            return $this->_qr->writeString();
        }
    }


    /**
     * 生成文件
     * @param string $file_name 目录文件 例: /tmp
     * @return
     */
    public function generateImg($file_name) {
        $file_path = $file_name . '\\' . uniqid() . '.' . self::WRITE_NAME;

        if (!file_exists($file_name)) {
            mkdir($file_name, 0777, true);
        }

        try {
            $this->_qr->writeFile($file_path);
//            $data = [
//                'url' => $file_path,
//                'ext' => self::WRITE_NAME,
//            ];
            return $file_path;
        } catch (Exception $e) {
            return false;
        }
    }
}