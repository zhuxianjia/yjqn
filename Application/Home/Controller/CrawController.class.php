<?php
namespace Home\Controller;
use Think\Controller;
class CrawController extends Controller {	
	
	public function b(){
		$url = 'http://vip.stock.finance.sina.com.cn/corp/go.php/vCI_CorpInfo/stockid/600005.phtml';
	    $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $code=curl_exec($ch);
        curl_close($ch);
	    $data = iconv("gbk","utf-8",$code);
	    $data=str_replace('&nbsp;', '', $data);
	    preg_match('/<td class="ct">主承销商：<\/td>\\s*<td class="cc">\\s*(<a.*>){0,1}([^<>]*)(<\/a>){0,1}\\s*<\/td>/', $data, $match_data);
	    preg_match_all('/<td colspan="3" class="ccl">(&nbsp;)*(.*)<\/td>/', $data, $match_data);
		dump($match_data);
	}


	 public function export(){
        $data = M('ainfo')->order('code')->field('sharename,code,name,enname,market,marketdate,price,founddate,registermoney,type,form,secretary,tele,secretarytele,fax,secretaryfax,email,secretaryemail,website,postcode,usedname,registeraddress,officeaddress,summary,field')->select();
        $length=count($data);
        for ($i = 0;$i<$length;$i++) {
            $data[$i]['code']=' '.$data[$i]['code'];
        //     if($data[$i]['markettype']=='sh_a') $data[$i]['mar']='沪市A股';
        //     else if($data[$i]['markettype']=='sh_b') $data[$i]['mar']='沪市B股';
        //     else if($data[$i]['markettype']=='sz_a') $data[$i]['mar']='深市A股';
        //     else if($data[$i]['markettype']=='sz_b') $data[$i]['mar']='深市B股';
        }
        //引入PHPExcel库文件（路径根据自己情况）
        include 'PHPExcel/Classes/PHPExcel.php';
        //创建对象
        $excel = new \PHPExcel();
        /*实例化excel图片处理类*/
        // $objDrawing = new \PHPExcel_Worksheet_Drawing();
        //Excel表格式,这里简略写了8列
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y');
        //表头数组
        $tableheader = array('股票简称','股票编号','公司名称','公司英文名称','上市市场','上市日期','发行价格','成立日期','注册资本','机构类型','组织形式','董事会秘书','公司电话','董秘电话','公司传真','董秘传真','公司电子邮箱','董秘电子邮箱','公司网址','邮政编码','证券简称更名历史','注册地址','办公地址','公司简介','经营范围');
    
        //填充表头信息
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]5","$tableheader[$i]");
        }
        //设置选定sheet表名
        $excel->getActiveSheet()->setTitle('上市公司信息汇总');
        //设置字体样式
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setName('Arial')->setSize(25)->setBold(true);//////->setUnderline(true);/////->getColor()->setARGB('FFFF0000');///->setBold(true);
        //合并单元格 给单元格赋值(数值，字符串，公式)
        $excel->getActiveSheet()->mergeCells('A1:Y3')->setCellValue('A1', '上市公司信息汇总');
        ///////$excel->getActiveSheet()->mergeCells('A4:D4')->setCellValue('A4', "=SUM(E4:F4)");

        $date_now  = date("Y-m-d H:i");
        $excel->getActiveSheet()->mergeCells('A4:Y4')->setCellValue('A4', "生成日期：".$date_now." "."　");

        //锁定窗格
        $excel->getActiveSheet()->freezePane('A6');

        //大边框样式 边框加粗
        $lineBORDER = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THICK,
                    'color' => array('argb' => '000000'),
                ),
            ),
        );
        //表头样式
        $head = array(
            'font'    => array(
               'bold'      => true
                ),
            'alignment' => array(
                        'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
                    ),
            
        );
        //标题样式
        $title = array(
            'font'    => array(
                'bold'      => true
            ),
        );
        //居中对齐
        $CENTER = array(
            'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
                ),
        );
        //靠右对齐
        $RIGHT = array(
            'alignment' => array(
                    'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
                    'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER
                ),
        );
        //细边框样式
        $linestyle = array(
            'borders' => array(
                'outline' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('argb' => 'FF000000'),
                ),
            ),
        );


        $excel->getActiveSheet()->getStyle('A1:Y3')->applyFromArray($head);///->getAlignment()->getHorizontal('');///->getBorders()->getTop()->setBorderStyle('');
        //->setWrapText(true);自动换行
        $excel->getActiveSheet()->getStyle('A4:Y4')->applyFromArray($RIGHT); 
        $excel->getActiveSheet()->getStyle('A5:Y5')->applyFromArray($title); 

        //填充色
        /////$excel->getActiveSheet()->getStyle('A1')->getFill()->getStartColor()->setARGB('FFFF0000');/

        //填充表格信息
        /*填充表格内容*/
        for ($i = 6;$i <= $length + 5;$i++) {//$i从初始写入数据行
            $j = 0;
            foreach ($data[$i - 6] as $key=>$value) {
                $excel->getActiveSheet()->setCellValue("$letter[$j]$i","$value");
                $excel->getActiveSheet()->getStyle("$letter[$j]$i")->applyFromArray($linestyle);
                $excel->getActiveSheet()->getRowDimension($i)->setRowHeight(60);
                $j++;
            }
        }
       
        //设置列宽度
        foreach ($letter as $value) {
            // if(in_array($value,array('A','D'))) $width=10;
            // else if(in_array($value,array('G','H','I'))) $width=15;
            $width=18;
            $excel->getActiveSheet()->getColumnDimension($value)->setWidth($width);
        }
        $row_count=$length+5;//加入表头、注释栏

        $excel->getActiveSheet()->getStyle('A5:Y'.$row_count)->applyFromArray($CENTER);
        $excel->getActiveSheet()->getStyle('A1:Y3')->applyFromArray($lineBORDER);
        $excel->getActiveSheet()->getStyle('A4:Y4')->applyFromArray($lineBORDER);
        $excel->getActiveSheet()->getStyle('A5:Y5')->applyFromArray($lineBORDER);
        $excel->getActiveSheet()->getStyle('A6:Y'.$row_count)->applyFromArray($lineBORDER);
        $excel->getActiveSheet()->getPageSetup()->setPaperSize(\PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        //设置自动筛选
        $excel->getActiveSheet()->setAutoFilter('A5:Y'.$row_count);
        //设置单元格格式  防止去0
        // $excel->getActiveSheet()->getStyle('A6:Y'.$row_count)->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        //设置自动换行
        $excel->getActiveSheet()->getStyle('A6:Y'.$row_count)->getAlignment()->setWrapText(true);
        $excel->getActiveSheet()->getStyle('A1:Y3')->getAlignment()->setWrapText(true);
        //创建Excel输入对象
        $write = new \PHPExcel_Writer_Excel5($excel);

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename=上市公司信息汇总.xls');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
        exit;
    }
    public function CompanyInfo(){
        $t=['sz_a'];
        // foreach ($t as $k => $v) {
            $markettype=substr($v,0,2);
            $url = 'http://vip.stock.finance.sina.com.cn/q/go.php/vInvestConsult/kind/qgqp/index.phtml?s_i=&s_a=&s_c=&s_t=sz_a&s_z=&p=1&num=10000';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $code=curl_exec($ch);
            curl_close($ch);
            $data = iconv("gbk","utf-8",$code);
            preg_match_all('/(sh|sz)([0-9]{6})/',$data,$match);
            $list=array_unique($match[2]);
            dump($list);
            flush();

            foreach ($list as $key => $value) {
                $url = 'http://vip.stock.finance.sina.com.cn/corp/go.php/vCI_CorpInfo/stockid/'.$value.'.phtml';
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL,$url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $code=curl_exec($ch);
                curl_close($ch);
                $data = iconv("gbk","utf-8",$code);
                $data=str_replace('&nbsp;', '', $data);
                preg_match_all('/公司简介—<\/span><.*>(.*)\\([0-9]+\\)<\/a>/', $data, $match_data);

                $save=['sharename'=>$match_data[1][0],'code'=>$value,'markettype'=>$v,'encode'=>$markettype.$value];
                preg_match_all('/<td class="ct">[^主].*<\/td>\\s*<td class="cc">([^<]*)<\/td>/', $data, $match_data);
                $temp=['price','registermoney','type','form','secretary','tele','secretarytele','fax','secretaryfax','secretaryemail','postcode'];
                foreach ($temp as $a => $m) {
                    $save[$m]=$match_data[1][$a];
                }

                preg_match_all('/<td class="ct">[^主].*<\/td>\\s*<td class="cc">\\s*<a.*>(.*)<\/a>/', $data, $match_data);
                $temp=['marketdate','email','website','url'];
                foreach ($temp as $c => $o) {
                    $save[$o]=$match_data[1][$c];
                }

                preg_match('/<td class="ct">主承销商：<\/td>\\s*<td class="cc">(<a.*>){0,1}(.*)(<\/a>){0,1}<\/td>/', $data, $match_data);
                $save['underwriter']=$match_data[1];

                preg_match('/<td class="ct">上市市场：<\/td>\\s*<td class="cc">([^<]*)<\/td>/', $data, $match_data);
                $save['market']=$match_data[1];

                preg_match('/<td class="ct">成立日期：.*<\/td>\\s*<td class="cc">\\s*<a.*>(.*)<\/a>/', $data, $match_data);
                $save['founddate']=$match_data[1];

                $data=$this->DeleteHtml($data);
                preg_match_all('/<tdcolspan="3"class="ccl">([^<>]*)<\/td>/', $data, $match_data);
                $temp=['name','enname','usedname','registeraddress','officeaddress','summary','field'];
                foreach ($temp as $b => $n) {
                    $save[$n]=$match_data[1][$b];
                }
                $res[]=$save;
                $condition['code']=$value;
                if(M('ainfo')->where($condition)->find()) M('ainfo')->where($condition)->save($save);
                else M('ainfo')->add($save);
            }
        // }
        echo json_encode($res);
        flush();
    }

    private function DeleteHtml($str) { 
        $str = str_replace("<br />","",$str);
        $str = str_replace(" ","",$str); 
        $str = str_replace("\r\n","",$str); 
        $str = str_replace("\r","",$str);
        $str = str_replace("\n","",$str); 
        $str = str_replace("\t","",$str); 
        return trim($str); 
    }
}