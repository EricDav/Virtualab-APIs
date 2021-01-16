<?php
 namespace VirtualLab\Helpers;

class  KeyGen
{
	const PASS_CHAR = "123456789ABCDEFGHJKLMNPQRTUVXYZ";
    const PASS_CHAR_PAD = "0";
    const ACTIVATION_KEY_SIZE = 10;
    const PRODUCT_KEY_SIZE = 12;
    const PIN_SIZE = 8;
    const NO_BIN_REPLICATE = 4;
    const PHYSICS_LAB_FILE_NAME = "PhysicsLab";

    public static function GenSeed($aStr)
	{
		$noChar = strlen($aStr);
		$theSeed = 0;
		for ($i = 0; $i < $noChar; $i++)
		{
			$theSeed = $theSeed + ($i + 1) * ord(substr($aStr,$i,1));
		}
		return $theSeed;
	}
	public static function ShuffleBin($aStr)
	{
		$randGen = new CustomRand(self::GenSeed($aStr),self::GenSeed(strrev($aStr)));
		$charList = self::SplitEachBin($aStr);
		$noChar = strlen($aStr);
		for ($i = 0; $i < $noChar; $i++)
		{
			$j = $randGen->Next($i, $noChar);
			if ($i != $j)
			{
				$holdStr = $charList[$i];
				$charList[$i] = $charList[$j];
				$charList[$j] = $holdStr;
			}
		}
		return implode($charList);
	}
	public static function SplitEachBin($aStr)
	{
		$noChar = strlen($aStr);
		$charList = array();
		for ($i = 0; $i < $noChar; $i++)
		{
			$charList[$i] = substr($aStr,$i,1);
		}
		return $charList;
	}
	public static function MutateBin($aStr)
	{
		$randGen = new CustomRand(self::GenSeed($aStr),self::GenSeed(strrev($aStr)));
		$noChar = strlen($aStr);
		$charList = "";

		for ($i = 0; $i < $noChar; $i++)
		{
			$j = $randGen->Next(0, 3);
			$ch = substr($aStr,$i,1);
			if ($j < 2)
			{
				$charList = $charList.$ch;
			}
			else
			{
				if ($ch == "0")
				{
					$charList = $charList."1";
				}
				else
				{
					$charList = $charList."0";
				}
			}
		}

		return $charList;
	}
	

	public static function HashKey($key, $salt, $size = -1)
	{
		$prodPass = "";
		if (strlen($key) == 0)
		{
			return $prodPass;
		}
		$PASS_CHAR = self::PASS_CHAR;
        $PASS_CHAR_PAD = self::PASS_CHAR_PAD;
        $NO_BIN_REPLICATE = self::NO_BIN_REPLICATE;

		$passCharSz = strlen($PASS_CHAR);
		$binStr = base_convert($passCharSz,10, 2);
		$binSz = strlen($binStr);
		$allProdKeyBin = "";
		$keySz = strlen($key);
		$nameSpace = $salt;
		$nameSpaceAry = str_split($nameSpace);
		for ($i = 0; $i < $keySz; $i++)
		{
			$keyCode = ord($key[$i]);
			$akeyBin = base_convert(ord($nameSpace[$keyCode%strlen($nameSpace)]), 10,2);
			$aStrNum = strpos($PASS_CHAR, $key[$i]);
			if ($aStrNum===false)
			{
				$aStrNum = $passCharSz - 1;
			}
			$allProdKeyBin .= $akeyBin . base_convert($keyCode, 10, 2) . self::ShuffleBin(str_pad(base_convert($aStrNum, 10,2),$binSz,$PASS_CHAR_PAD,STR_PAD_LEFT));
		}
		$hldAllProdKeyBin = "";
		for ($j = 0; $j < $NO_BIN_REPLICATE; $j++)
		{
			$hldAllProdKeyBin = $hldAllProdKeyBin.$allProdKeyBin;
		}
		$allProdKeyBin1 = self::ShuffleBin($hldAllProdKeyBin);
		$allProdKeyBin2 = $hldAllProdKeyBin;
		$midPoint = floor(strlen($hldAllProdKeyBin)/ 2);
		$crossProdKeyBin = self::MutateBin(substr($allProdKeyBin1,0, $midPoint)).self::MutateBin(substr($allProdKeyBin2,$midPoint));
		
		$allBinSz = strlen($crossProdKeyBin);
		for ($j = 0; $j < $allBinSz; $j += $binSz)
		{
			if ($j + $binSz > $allBinSz) break;
			$abin = substr($crossProdKeyBin, $j, $binSz);
			$posInPassChar = base_convert($abin, 2,10) % $passCharSz;
			$prodPass = $prodPass.$PASS_CHAR[$posInPassChar];
		}
		$size = min($size, strlen($prodPass));
		return substr(self::ShuffleBin($prodPass),0, $size);
	}
	
}

