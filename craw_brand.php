<?php
/**
 * Created by PhpStorm.
 * User: simon-张俊龙
 * Date: 17/7/25
 * Time: 下午3:07
 */
    set_time_limit(0);
    $file_content = file_get_contents("http://car.bitauto.com/brandlist.html");
    $home_link = "http://car.bitauto.com";
    //获取整个品牌列表
    $regx = "/<dl class=\"bybrand_list\">(.*?)<\/dl>/is";
    preg_match_all($regx,$file_content,$mathch);
    //获取所有品牌名称
    $regx_brandName = "/<div class=\"brandname\"><a href=\".*?\" target=\"_blank\">(.*?)<\/a><\/div>/is";
    preg_match_all($regx_brandName,$mathch[0][0],$mathch_brandName);
    $brandName = $mathch_brandName[1];
    //获取整个品牌系列
    $regx_brandNameNext = "/<dd class=\"have\">(.*?)<\/dd>/is";
    preg_match_all($regx_brandNameNext,$mathch[0][0],$mathch_brandNameNext);
    //获取系列列表
    $regx_brandXl = "/<h2.*?><a href=\".*?\" target=\"_blank\">(.*?)<\/a><\/h2>/is";
    $regx_a = "/<a href=\".*?\" target=\"_blank\">(.*)<\/a>/is";
    $regx_ul = "/<ul>(.*?)<\/ul>/is";
    $regx_li = "/<li><div class=\"name\"><a href=\"(.*?)\" target=\"_blank\">(.*?)<\/a><a href=\".*?\" class=\"classify\" target=\"_blank\">(.*?)<\/a>.*?<\/div><div class=\".*?\">(.*?)<\/div><\/li>/is";
    $carBrand = [];
    foreach($mathch_brandNameNext[0] as $item => $value){
        preg_match_all($regx_brandXl,$value,$xlArray);
        $carBrand[$item]['name'] = $brandName[$item];
        preg_match_all($regx_ul,$value,$xlNextArray);
        foreach($xlNextArray[1] as $xnKey=>$xnValue){
            preg_match_all($regx_li,$xnValue,$xnArray);
            $car = [];
            foreach($xnArray[2] as $k => $v){
                $xhTempArray = [];
                $car[$k]['name'] = $v;
                $car[$k]['zdj'] = $xnArray[4][$k];
                $car[$k]['type'] = $xnArray[3][$k];
                $llink = $home_link.$xnArray[1][$k];
                $link_content = file_get_contents($llink);
                $regx_xh = "/<td class=\"txt-left\" id=\".*?\"><a href=\".*?\" .*?>(.*?)<\/a>.*?<\/td>/is";
                preg_match_all($regx_xh,$link_content,$xhArray);
                $regx_zjl = "/<td class=\"txt-right overflow-visible\"><span>(.*?)<\/span>/is";
                preg_match_all($regx_zjl,$link_content,$zdjArray);
                foreach($xhArray[1] as $xhk => $xhv){
                    $xhTempArray[$xhk]['name'] = $xhArray[1][$xhk];
                    $xhTempArray[$xhk]['zdj'] = $zdjArray[1][$xhk];
                    if(strstr($xhArray[0][$xhk],"停产")){
                        $xhTempArray[$xhk]['is_tc'] = 1;
                    }else{
                        $xhTempArray[$xhk]['is_tc'] = 0;
                    }
                }
                $car[$k]['data'] = $xhTempArray;
                unset($xhTempArray);
            }
            $carBrand[$item]['data'][$xnKey]['name'] = $xlArray[1][$xnKey];
            $carBrand[$item]['data'][$xnKey]['data'] = $car;
            unset($car);
        }
    }
    foreach($carBrand as $key=>$value){
        foreach($value['data'] as $dk => $dv){
            if(strstr($dv['name'],"进口")){
                $carBrand[$key]['data'][$dk]['carfrom_type'] = 2;
                $tempArr = $dv;
                $tempArr['name'] = $dv['name']."(平行进口车)";
                $tempArr['carfrom_type'] = 3;
                $carBrand[$key]['data'][count($carBrand[$key]['data'])] = $tempArr;
                unset($tempArr);
            }else{
                $carBrand[$key]['data'][$dk]['carfrom_type'] = 1;
            }
        }
    }

    //ID
    //车牌
    //型号
    //系列
    $i = 0;
    unset($data);
    foreach($carBrand as $key => $value){
        $data[$i]['name'] = $value['name'];
        $data[$i]['level']  = 1;
        $data[$i]['id'] = $i+1;
        $data[$i]['pid'] = 0;
        $pid = $i+1;
        $data[$i]['zdj'] ="";
        $data[$i]['is_tc'] =0;
        $data[$i]['cartype'] = "";
        $data[$i]['carfrom_type'] = "";
            foreach($value['data'] as $k => $v){
                $i++;
                $data[$i]['name'] = $v['name'];
                $data[$i]['level'] = 2;
                $data[$i]['id'] = $i+1;
                $data[$i]['pid'] = $pid;
                $pid_ = $i+1;
                $data[$i]['zdj'] ="";
                $data[$i]['is_tc'] =0;
                $data[$i]['cartype'] = "";
                $data[$i]['carfrom_type'] = $v['carfrom_type'];
                foreach($v['data'] as $item => $itemV){
                    $i++;
                    $data[$i]['name'] = $itemV['name'];
                    $data[$i]['level'] = 3;
                    $data[$i]['id'] = $i+1;
                    $data[$i]['pid'] = $pid_;
                    $pid__ = $i+1;
                    $data[$i]['zdj'] =$itemV['zdj'];
                    $data[$i]['is_tc'] =0;
                    $data[$i]['cartype'] = $itemV['type'];
                    $data[$i]['carfrom_type'] = "";
                    foreach ($itemV['data'] as $x => $y){
                        $i++;
                        $data[$i]['name'] = $y['name'];
                        $data[$i]['level'] = 4;
                        $data[$i]['id'] = $i+1;
                        $data[$i]['pid'] = $pid__;
                        $data[$i]['zdj'] =$y['zdj'];
                        $data[$i]['is_tc'] =0;
                        $data[$i]['cartype'] = $itemV['type'];
                        $data[$i]['carfrom_type'] = "";
                    }
                }
            }
        $i++;
    }
    $sql = "insert into brand (id,name,level,pid,guideprice,is_tc,cartype,carfrom_type) VALUES ";
    foreach($data as $key=>$value){
        $sql .= "(".$value['id'].",'".$value['name']."','".$value['level']."','".$value['pid']."','".$value['zdj']."','".$value['is_tc']."','".$value['cartype']."','".$value['carfrom_type']."'),";
    }
    $sql = substr($sql,0,strlen($sql)-1);
    file_put_contents("./brand.sql",$sql);
    echo $sql;