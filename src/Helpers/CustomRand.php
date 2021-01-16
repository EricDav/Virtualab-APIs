<?php
 namespace VirtualLab\Helpers;

class CustomRand
{
	private static $m_z = 57654;
	private static $m_w = 82168;
	const pow32 = 4294967296;
	function __construct($init_m_z, $init_m_w)
	{
		self::$m_z = $init_m_z;
		self::$m_w = $init_m_w;
		
	}
	private function GetRandomUnit()
	{
		self::$m_z = (36969 * self::BitAnd(self::$m_z, 65535) + self::ShiftBitRight(self::$m_z, 16));
		if (self::$m_z >= self::pow32)
		{
			self::$m_z = self::$m_z - self::pow32;
		}
		self::$m_w = (18000 * self::BitAnd(self::$m_w, 65535) + self::ShiftBitRight(self::$m_w, 16));
		if (self::$m_w >= self::pow32)
		{
			self::$m_w = self::$m_w - self::pow32;
		}
		$hldRnd = (self::ShiftBitLeft(self::$m_z, 16) + self::$m_w);
		if ($hldRnd >= self::pow32)
		{
			$hldRnd = $hldRnd - self::pow32;
		}
		return $hldRnd;
	}
	public function Next($minNo, $maxNo)
	{
		$hld = $this->Unif();
		return floor(($maxNo - $minNo) * $hld) + $minNo;
	}
	public function Unif()
	{
		$u = $this->GetRandomUnit();
		return ($u + 1.0)/(self::pow32 + 2);
	}
	public static function ShiftBitLeft($num,$n, $bit=32)
	{
		$baseNum = str_pad(base_convert($num,10,2)."",$bit,"0",STR_PAD_LEFT);
		return base_convert(str_pad(substr($baseNum,$n),$bit,"0",STR_PAD_RIGHT),2,10);
	}
	public static function ShiftBitRight($num,$n,$bit=32)
	{
		$baseNum = str_pad(base_convert($num,10,2)."",$bit,"0",STR_PAD_LEFT);
		$sz = $bit-$n;
		return base_convert(str_pad(substr($baseNum,0,$sz),$bit,"0",STR_PAD_LEFT),2,10);
	}
	public static function BitAnd($num1, $num2, $bit=32)
	{
		$baseNum1 = str_pad(base_convert($num1,10,2)."",$bit,"0",STR_PAD_LEFT)."";
		$baseNum2 = str_pad(base_convert($num2,10,2)."",$bit,"0",STR_PAD_LEFT)."";
		$retVal = "";
		for ($i=0;$i<$bit;$i++)
		{
			if (substr($baseNum1,$i,1)=="1" && substr($baseNum2,$i,1)=="1")
			{
				$retVal = $retVal."1";
			}
			else
			{
				$retVal = $retVal."0";
			}
		}
		return base_convert($retVal,2,10);
	}
	public static function BitOr($num1, $num2, $bit = 32)
	{
		$baseNum1 = str_pad(base_convert($num1,10,2)."",$bit,"0",STR_PAD_LEFT) + "";
		$baseNum2 = str_pad(base_convert($num2,10,2)."",$bit,"0",STR_PAD_LEFT) + "";
		$retVal = "";
		for ($i=0;$i<$bit;$i++)
		{
			if (substr($baseNum1,$i,1)=="1" || substr($baseNum2,$i,1)=="1")
			{
				$retVal = $retVal."1";
			}
			else
			{
				$retVal = $retVal."0";
			}
		}
		return base_convert($retVal,2,10);
	}
}
?>
