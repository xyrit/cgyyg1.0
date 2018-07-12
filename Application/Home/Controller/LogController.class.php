<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/4/9
 * Time: 13:13
 */

namespace Home\Controller;


class LogController extends HomeController {


    /*
     *
     * 日志
     *
     */
    function setlog($stri='',$data=0,$action=0){

//        $data=array(
//            'aaa'=>'123',
//            'bbb'=>'456',
//            'ccc'=>'789',
//            'ddd'=>array(
//                'a1'=>1,
//                'a2'=>2,
//                'a3'=>3,
//                'a4'=>array(
//                    'a1'=>1,
//                    'a2'=>2,
//                    'a3'=>3,
//                )
//            )
//        );
//        $stri ="支付之前的数据";
        $time=time();
        $atime = date('Y-m-d',$time);
        $ytime = date('Y-m-d h:i:s',$time);
        $file = DOC_ROOT_PATH.'payLog/'.$atime.'.txt';
        $str="--------------------------------------------\r\n";
        $str.="\r\n$ytime------".$stri."\r\n";
        $str.="地址：".$action;
        if(is_array($data))
        {


            //file_put_contents($file,serialize($array));//写入缓存
           $str.=$this->Arraydata($data);
        }
        else
        {
            $str.=$data."\\r\n";

        }
        file_put_contents($file,$str,FILE_APPEND);
    }

    function Arraydata($data,$ci=0){

        $space="  ";
       // $space2="  ";
        //空格处理
        for($i=0;$i<$ci;$i++)
        {
            $space.="  ";
        }


        $str=$space."Array\r\n".$space."{\r\n";
        foreach($data as $key=>$row)
        {
            if(is_array($row))
            {
                $ci++;
                $str.=$space;
                $str.= $this->Arraydata($row,$ci);

            }
            else
            {
                $str .=$space;
                $str .= "$key=>$row\r\n";

            }

        }

        $str.=$space."}\r\n";
        return $str;

    }
}