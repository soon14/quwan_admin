<?php
namespace Admin\Model;
use Think\Model;
/**
* geoHash算法
* @date: 2017年11月23日 上午11:34:29
* @author: bobo
* @qq:273719650
* @email:273719650@qq.com
* @version:
* ==============================================
* 版权所有 2017-2017 http://www.bobolucy.com
* ==============================================
*/
class GeoHashModel extends Model {
    Protected $autoCheckFields = false;
    
    private $coding="0123456789bcdefghjkmnpqrstuvwxyz";  
    private $codingMap=array();  
    
    public function Geohash()  
    {  
        for($i=0; $i<32; $i++)  
        {  
            $this->codingMap[substr($this->coding,$i,1)]=str_pad(decbin($i), 5, "0", STR_PAD_LEFT);  
        }  
    
    }  
    
    public function decode($hash)  
    {  
        $binary="";  
        $hl=strlen($hash);  
        for($i=0; $i<$hl; $i++)  
        {  
            $binary.=$this->codingMap[substr($hash,$i,1)];  
        }  
    
        $bl=strlen($binary);  
        $blat="";  
        $blong="";  
        for ($i=0; $i<$bl; $i++)  
        {  
            if ($i%2)  
                $blat=$blat.substr($binary,$i,1);  
            else  
                $blong=$blong.substr($binary,$i,1);  
    
        }  
    
         $lat=$this->binDecode($blat,-90,90);  
         $long=$this->binDecode($blong,-180,180);  
        // $lat=$this->binDecode($blat,2,54);  
        // $long=$this->binDecode($blong,72,136);  
    
        $latErr=$this->calcError(strlen($blat),-90,90);  
        $longErr=$this->calcError(strlen($blong),-180,180);  
    
        $latPlaces=max(1, -round(log10($latErr))) - 1;  
        $longPlaces=max(1, -round(log10($longErr))) - 1;  
    
        $lat=round($lat, $latPlaces);  
        $long=round($long, $longPlaces);  
    
        return array($lat,$long);  
    }  
    
    public function encode($lat,$long)  
    {  
        $plat=$this->precision($lat);  
        $latbits=1;  
        $err=45;  
        while($err>$plat)  
        {  
            $latbits++;  
            $err/=2;  
        }  
    
        $plong=$this->precision($long);  
        $longbits=1;  
        $err=90;  
        while($err>$plong)  
        {  
            $longbits++;  
            $err/=2;  
        }  
    
        $bits=max($latbits,$longbits);  
    
        $longbits=$bits;  
        $latbits=$bits;  
        $addlong=1;  
        while (($longbits+$latbits)%5 != 0)  
        {  
            $longbits+=$addlong;  
            $latbits+=!$addlong;  
            $addlong=!$addlong;  
        }  
    
        $blat=$this->binEncode($lat,-90,90, $latbits);  
    
        $blong=$this->binEncode($long,-180,180,$longbits);  
    
        $binary="";  
        $uselong=1;  
        while (strlen($blat)+strlen($blong))  
        {  
            if ($uselong)  
            {  
                $binary=$binary.substr($blong,0,1);  
                $blong=substr($blong,1);  
            }  
            else  
            {  
                $binary=$binary.substr($blat,0,1);  
                $blat=substr($blat,1);  
            }  
            $uselong=!$uselong;  
        }  
    
        $hash="";  
        for ($i=0; $i<strlen($binary); $i+=5)  
        {  
            $n=bindec(substr($binary,$i,5));  
            $hash=$hash.$this->coding[$n];  
        }  
    
        return $hash;  
    }  
    
    private function calcError($bits,$min,$max)  
    {  
        $err=($max-$min)/2;  
        while ($bits--)  
            $err/=2;  
        return $err;  
    }  
    
    private function precision($number)  
    {  
        $precision=0;  
        $pt=strpos($number,'.');  
        if ($pt!==false)  
        {  
            $precision=-(strlen($number)-$pt-1);  
        }  
    
        return pow(10,$precision)/2;  
    }  
    
    private function binEncode($number, $min, $max, $bitcount)  
    {  
        if ($bitcount==0)  
            return "";  
        $mid=($min+$max)/2;  
        if ($number>$mid)  
            return "1".$this->binEncode($number, $mid, $max,$bitcount-1);  
        else  
            return "0".$this->binEncode($number, $min, $mid,$bitcount-1);  
    }  
    
    private function binDecode($binary, $min, $max)  
    {  
        $mid=($min+$max)/2;  
    
        if (strlen($binary)==0)  
            return $mid;  
    
        $bit=substr($binary,0,1);  
        $binary=substr($binary,1);  
    
        if ($bit==1)  
            return $this->binDecode($binary, $mid, $max);  
        else  
            return $this->binDecode($binary, $min, $mid);  
    }  
    
    /**
     *  @desc 根据两点间的经纬度计算距离
     *  @param float $latitude 纬度值
     *  @param float $longitude 经度值
     */
    public function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $earth_radius = 6371000;   //approximate radius of earth in meters
    
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);
        /*
         Using the
         Haversine formula
    
         http://en.wikipedia.org/wiki/Haversine_formula
         http://www.codecodex.com/wiki/Calculate_Distance_Between_Two_Points_on_a_Globe
         验证：百度地图  http://developer.baidu.com/map/jsdemo.htm#a6_1
         calculate the distance
         */
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * asin(sqrt($a));
        $d = $earth_radius * $c;
    
        return round($d);   //四舍五入
    }
}  